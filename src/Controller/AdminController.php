<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\SubmissionWorkflow;
use App\Entity\Submission;
use App\Repository\SubmissionRepository;
use App\Service\EmailService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;


final class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    private EmailService $emailService;



    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, EmailService $emailService)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/candidates', name: 'admin_candidates')]
    public function listCandidates(): Response
    {

        $submissions = $this->entityManager->getRepository(Submission::class)->findAll();

        return $this->render('admin/candidates_list.html.twig', [
            'submissions' => $submissions,
        ]);
    }

    #[Route('/admin/selected_candidates', name: 'admin_selected_candidates')]
    public function selectedCandidates(SubmissionRepository $submissionRepository): Response
    {
        $submissions = $submissionRepository->findBy([
            'isCandidateAccepted' => true,
        ]);

        return $this->render('admin/selected_candidates.html.twig', [
            'submissions' => $submissions,
        ]);
    }

    #[Route('/admin/candidate/{id}', name: 'admin_view_candidate')]
    public function viewCandidate(int $id, SubmissionRepository $submissionRepository, UserRepository $userRepository): Response
    {
        $query = $this->entityManager->createQuery("
            SELECT s, c, p, u, e, sw, ws FROM App\Entity\Submission s
            JOIN s.candidateProfile c
            JOIN c.userProfile p
            JOIN p.user u
            LEFT JOIN s.editions e
            LEFT JOIN s.submissionWorkflows sw
            LEFT JOIN u.workflowStates ws
            WHERE s.id = :id
        ")->setParameter('id', $id);
    
        $submission = $query->getOneOrNullResult();
    
        if (!$submission) {
            $this->addFlash('danger', 'Candidate not found.');
            return $this->redirectToRoute('admin_candidates');
        }
        
        $juryCount = $userRepository->countByRole('ROLE_JURY');
        
        
        return $this->render('admin/candidate_profile.html.twig', [
            'submission' => $submission,
            'jury_count' => $juryCount,
        ]);
    }

    #[Route('/admin/candidate/{id}/accept', name: 'admin_accept_candidate')]
    public function acceptCandidate(int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException('Candidate submission not found.');
        }
        
        // Get active jury members count
        $totalJuryCount = $userRepository->countByRoleName('ROLE_JURY');
        
        // Count unique jury evaluations
        $evaluatedJuryIds = [];
        foreach ($submission->getEvaluations() as $evaluation) {
            $jury = $evaluation->getJury();
            if ($jury !== null && 
                $jury->getRole()->getName() === 'ROLE_JURY' && 
                $jury->isActive()) {
                $evaluatedJuryIds[$jury->getId()] = true;
            }
        }
        $evaluatedJuryCount = count($evaluatedJuryIds);
        
        // Debug info
        $this->addFlash('info', "Evaluations: $evaluatedJuryCount of $totalJuryCount juries");
        
        if ($evaluatedJuryCount < $totalJuryCount) {
            $this->addFlash('warning', 'All jury members must evaluate before making a decision.');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }
        
        // Prevent re-accepting an already accepted candidate
        if ($submission->isCandidateAccepted()) {
            $this->addFlash('warning', 'This candidate has already been accepted.');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }
        
        // Accept the candidate
        $submission->setIsCandidateAccepted(true);
        $submission->setCurrentState('candidate_approved');
        
        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('candidate_approved');
        $workflow->setTransltionedAt(new \DateTime());
        
        $entityManager->persist($workflow);
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidate has been successfully accepted.');
        return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
    }

    #[Route('/admin/candidate/{id}/reject', name: 'admin_reject_candidate')]
    public function rejectCandidate(int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);

        if (!$submission) {
            throw $this->createNotFoundException('Candidate submission not found.');
        }

        $juryMembers = $userRepository->findByRoleName('ROLE_JURY');
        $totalJuryCount = count($juryMembers);
        $juryEvaluations = array_filter(
            $submission->getEvaluations()->toArray(),
            function ($evaluation) {
                $jury = $evaluation->getJury();
        
                return $jury !== null 
                    && in_array('ROLE_JURY', $jury->getRoles()) 
                    && $jury->isActive() // Check if the user is active
                    && (!$jury->getUserProfile() || !$jury->getUserProfile()->isArchived()); // Ensure not archived
            }
        );
        $evaluatedJuryCount = count($juryEvaluations);

        if ($evaluatedJuryCount != $totalJuryCount) {
            $this->addFlash('warning', 'All jury members must evaluate before making a decision.');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }

        if (!$submission->isCandidateAccepted()) {
            $this->addFlash('warning', 'This candidate has already been rejected.');
            return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
        }

        $submission->setIsCandidateAccepted(false);
        $submission->setCurrentState('candidate_rejected');

        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('candidate_rejected');
        $workflow->setTransltionedAt(new \DateTime());

        $entityManager->persist($workflow);
        $entityManager->flush();

        $this->addFlash('danger', 'Candidate has been rejected.');
        return $this->redirectToRoute('admin_view_candidate', ['id' => $id]);
    }
}
