<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Submission;
use App\Entity\Evaluation;
use App\Entity\SubmissionWorkflow;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/jury/evaluation')]
#[IsGranted('ROLE_JURY')] // Sécurité : accès réservé aux jurys
final class EvaluationController extends AbstractController
{
    private $entityManager;
    private $emailService;
    private $translator;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
        $this->translator = $translator;
    }
    
    #[Route('/noter/{id}', name: 'jury_noter_submission', methods: ['GET', 'POST'])]
    public function noter(
        Submission $submission,
        Request $request,
        EvaluationRepository $evaluationRepository
    ): Response {
        $jury = $this->getUser();
        $candidat = $submission->getCandidateProfile(); // Utilisation correcte de la relation
        
        // Check if the jury has already evaluated this submission
        $existingEvaluation = $evaluationRepository->findOneBy([
            'jury' => $jury,
            'submission' => $submission
        ]);
        
        if ($existingEvaluation) {
            $evaluation = $existingEvaluation;
            $evaluation->setUpdatedAt(new \DateTimeImmutable());
            $isNewEvaluation = false;
        } else {
            $evaluation = new Evaluation();
            $evaluation->setJury($jury);
            $evaluation->setCandidat($candidat);
            $evaluation->setSubmission($submission);
            $evaluation->setCreatedAt(new \DateTimeImmutable());
            $isNewEvaluation = true;
        }
        
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Update the submission state if it's a new evaluation
            if ($isNewEvaluation) {
                $this->updateSubmissionEvaluationState($submission);
            }
            
            $this->entityManager->persist($evaluation);
            $this->entityManager->flush();
            
            // Send notifications about the evaluation
            $this->sendEvaluationNotifications($submission, $evaluation, $isNewEvaluation);
            
            $this->addFlash(
                'success', 
                $isNewEvaluation 
                    ? $this->translator->trans('evaluation.success.created') 
                    : $this->translator->trans('evaluation.success.updated')
            );
            
            return $this->redirectToRoute('admin_view_candidate', ['id'=> $submission->getId()]);
        }
        
        return $this->render('evaluation/noter.html.twig', [
            'submission' => $submission,
            'candidat' => $candidat,
            'form' => $form->createView(),
            'isEdit' => !$isNewEvaluation
        ]);
    }
    
    #[Route('/list', name: 'jury_liste_submissions', methods: ['GET'])]
    public function listeSubmissions(EvaluationRepository $evaluationRepository): Response
    {
        $jury = $this->getUser();
        $submissions = $evaluationRepository->findSubmissionsForJury($jury);
        
        return $this->render('evaluation/liste.html.twig', [
            'submissions' => $submissions,
        ]);
    }
    
    /**
     * Update the submission workflow state to reflect evaluation activity
     */
    private function updateSubmissionEvaluationState(Submission $submission): void
    {
        // Check if this is the first evaluation or if there are existing ones
        $evaluationsCount = $this->entityManager->getRepository(Evaluation::class)
            ->count(['submission' => $submission]);
        
        $currentState = $submission->getCurrentState();
        $newState = $evaluationsCount === 0 ? 'evaluation_started' : 'evaluation_in_progress';
        
        // Only update the state if it's different
        if ($currentState !== $newState) {
            $submission->setCurrentState($newState);
            
            // Create a new workflow state entry
            $workflow = new SubmissionWorkflow();
            $workflow->setSubmission($submission);
            $workflow->setState($newState);
            $workflow->setTransltionedAt(new \DateTime());
            
            $this->entityManager->persist($workflow);
        }
    }
    
    /**
     * Send appropriate notifications based on evaluation status
     */
    private function sendEvaluationNotifications(
        Submission $submission, 
        Evaluation $evaluation, 
        bool $isNewEvaluation
    ): void {
        // Get the candidate's user object for email notifications
        $candidateUser = $submission->getCandidateProfile()->getUserProfile()->getUser();
        
        // Get admin users who should be notified
        $adminUsers = $this->entityManager->getRepository(User::class)->findByRoleName('ROLE_ADMIN');
        
        // For new evaluations
        if ($isNewEvaluation) {
            // Notify candidate that their submission is being evaluated
            $this->emailService->sendSubmissionEvaluationStartedEmail($candidateUser, $submission);
            
            // Notify admins about the new evaluation
            foreach ($adminUsers as $adminUser) {
                $this->emailService->sendAdminEvaluationNotificationEmail(
                    $adminUser, 
                    $submission, 
                    $evaluation,
                    $this->getUser() // The jury member
                );
            }
        } else {
            // For updated evaluations, only notify admins
            foreach ($adminUsers as $adminUser) {
                $this->emailService->sendAdminEvaluationUpdatedNotificationEmail(
                    $adminUser, 
                    $submission, 
                    $evaluation,
                    $this->getUser() // The jury member
                );
            }
        }
        
        // If all jury members have now submitted evaluations, notify admins
        $allJuryCount = $this->entityManager->getRepository(User::class)
            ->countByRoleName('ROLE_JURY');
            
        // Count unique jury evaluations for this submission
        $evaluatedJuryIds = [];
        foreach ($submission->getEvaluations() as $eval) {
            $jury = $eval->getJury();
            if ($jury !== null && 
                $jury->getRole()->getName() === 'ROLE_JURY' && 
                $jury->isActive()) {
                $evaluatedJuryIds[$jury->getId()] = true;
            }
        }
        $submissionEvaluationCount = count($evaluatedJuryIds);
            
        if ($allJuryCount > 0 && $allJuryCount === $submissionEvaluationCount) {
            // All jury members have submitted evaluations
            $submission->setCurrentState('evaluation_completed');
            
            // Create a new workflow state entry
            $workflow = new SubmissionWorkflow();
            $workflow->setSubmission($submission);
            $workflow->setState('evaluation_completed');
            $workflow->setTransltionedAt(new \DateTime());
            
            $this->entityManager->persist($workflow);
            
            // Notify admins that all evaluations are complete
            foreach ($adminUsers as $adminUser) {
                $this->emailService->sendAllEvaluationsCompletedEmail(
                    $adminUser,
                    $submission
                );
            }
        }
    }
}