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

    #[Route('/application/finish', name:'app_apply_finish')]
    public function finish(Request $request): Response
    {
       $user = $this->getUser();
       if (!$user) {
           $this->addFlash('error', 'You must be logged in.');
           return $this->redirectToRoute('app_login');
       }
    
       $candidateProfile = $user->getUserProfile()?->getCandidateProfile();
       if (!$candidateProfile) {
           throw $this->createNotFoundException('Candidate profile not found');
       }
    
       $submission = $this->submissionRepository->findOneBy([
           'candidateProfile' => $candidateProfile,
       ]);
    
       if (!$submission) {
           throw $this->createNotFoundException('No active submission found');
       }
    
       $combinedForm = $this->createFormBuilder(null, [
        'csrf_protection' => true,
        'method' => 'POST',
        'action' => $this->generateUrl('app_apply_finish')
    ])
        ->add('subject', SubjectStudyType::class, [
            'data' => new SubjectStudy()
        ])
        ->add('cv', CandidateCvType::class, [
            'data' => $candidateProfile
        ])
        ->getForm();
    
       $combinedForm->handleRequest($request);
       
       if ($combinedForm->isSubmitted()) {
        if (!$combinedForm->isValid()) {
            dump($combinedForm->getErrors(true));
        }
    }
       if ($request->isMethod('POST') && $combinedForm->isSubmitted() && $combinedForm->isValid()) {
           $data = $combinedForm->getData();
           $subject = $data['subject'];
           
           $submission->setSubject($subject);
           $subject->setSubmission($submission);
           
           $submission_workflow = new SubmissionWorkflow();
           $submission_workflow->setSubmission($submission);
           $submission_workflow->setState('under_review');
           $submission->addSubmissionWorkflow($submission_workflow);
    
           $this->entityManager->persist($subject);
           $this->entityManager->persist($submission);
           $this->entityManager->persist($submission_workflow);
           $this->entityManager->persist($candidateProfile);
           $this->entityManager->flush();

           $this->addFlash('success','Your application has been submitted successfully.');
    
           return $this->redirectToRoute('application_confirmation', [
               'id' => $submission->getId(),
           ]);
       }
    
       return $this->render('apply/finish_submission.html.twig', [
           'form' => $combinedForm->createView(),
           'submission' => $submission
       ]);
    }

    
}
