<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\Submission;
use App\Form\CandidateProfileType;
use App\Entity\UserProfile;
use App\Service\SubmissionWorkflowService;
use App\Entity\SubmissionWorkflow;
use App\Repository\UserProfileRepository;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ApplyController extends AbstractController
{
    #[Route('/apply', name: 'app_apply_page')]
    public function index(): Response
    {
        return $this->render('apply/index.html.twig', [
            'controller_name' => 'ApplyController',
        ]);
    }

    #[Route('/apply/{id}', name: 'app_apply')]
    public function apply(
    int $id,
    EntityManagerInterface $entityManager,
    Request $request,
    SubmissionWorkflowService $submissionWorkflowService,
    ): Response {
        $userProfile = $this->getUser()->getUserProfile();

        if (!$userProfile) {
            throw $this->createNotFoundException('UserProfile not found.');
        }

        $candidateProfile = $userProfile->getCandidateProfile();
        $isNewCandidateProfile = false;

        if (!$candidateProfile) {
            $candidateProfile = new CandidateProfile();
            $candidateProfile->setUserProfile($userProfile);
            $isNewCandidateProfile = true;
        }

        $existingSubmission = $entityManager->getRepository(Submission::class)
            ->findOneBy(['candidateProfile' => $candidateProfile]);

        if ($existingSubmission) {
            return $this->redirectToRoute('application_confirmation', [
                'id' => $existingSubmission->getId(),
            ]);
        }

        $form = $this->createForm(CandidateProfileType::class, $candidateProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('studentCardPath')->getData();

            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('upload_directory'), $fileName);
                $candidateProfile->setStudentCardPath($fileName);
            }

            $submission = new Submission();
            $submission->setCandidateProfile($candidateProfile);
            $submission->setCurrentState('submitted');
            $submission->setIdentifier(bin2hex(random_bytes(5)));
            $submission->setIsSubmissionAccepted(false);
            $submission->setIsCandidateAccepted(false);

            if ($isNewCandidateProfile) {
                $entityManager->persist($candidateProfile);
            }
            $workflow = new SubmissionWorkflow();
            $workflow->setState('submitted');
            $submission->addSubmissionWorkflow($workflow);
            $entityManager->persist($workflow);
            $entityManager->persist($submission);
            $entityManager->flush();

            return $this->redirectToRoute('application_confirmation', [
                'id' => $submission->getId(),
            ]);
        }

        return $this->render('apply/candidate_profile_form.html.twig', [
            'form' => $form->createView(),
            'submission' => null,
        ]);
    }

    #[Route('/apply/confirmation/{id}', name: 'application_confirmation')]
    public function confirmation(Submission $submission): Response
    {
        return $this->render('apply/confirmation.html.twig', [
            'submission' => $submission,
        ]);
    }

    
}
