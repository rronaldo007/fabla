<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\Submission;
use App\Form\CandidateProfileType;
use App\Service\SubmissionWorkflowService;
use App\Entity\SubmissionWorkflow;
use App\Repository\SubmissionRepository;
use App\Form\SubjectStudyType;
use App\Form\CandidateCvType;
use App\Entity\SubjectStudy;
use App\Entity\SharedResource;
use App\Repository\EditionRepository;
use App\Entity\Edition;
use App\Entity\User;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ApplyController extends AbstractController
{
    private $entityManager;
    private $submissionRepository;
    private $slugger;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubmissionRepository $submissionRepository,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->submissionRepository = $submissionRepository;
        $this->slugger = $slugger;
    }

    #[Route('/apply_page', name: 'app_apply_page')]
    public function index(EditionRepository $editionRepository): Response
    {
        $editions = $editionRepository->findBy([], ['year' => 'DESC']);
        
        return $this->render('apply/index.html.twig', [
            'controller_name' => 'ApplyController',
            'editions' => $editions,
        ]);
    }

    #[Route('/apply/{id}', name: 'app_apply')]
    public function apply(
        int $id,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        // Get the edition first
        $edition = $entityManager->getRepository(Edition::class)->find($id);
        if (!$edition) {
            return $this->redirectToRoute('app_four_o_four');
        }

        $userProfile = $this->getUser()->getUserProfile();

        $existingSubmission = $entityManager->getRepository(Submission::class)
        ->createQueryBuilder('s')
        ->join('s.editions', 'e')
        ->join('s.candidateProfile', 'cp')
        ->join('cp.userProfile', 'up')
        ->where('e.id = :editionId')
        ->andWhere('up.id = :userProfileId')
        ->setParameter('editionId', $id)
        ->setParameter('userProfileId', $userProfile->getId())
        ->getQuery()
        ->getOneOrNullResult();

        if ($existingSubmission) {
            $this->addFlash('danger', 'You already have a submission for this edition.');
            return $this->redirectToRoute('application_confirmation', [
                'id' => $existingSubmission->getId(),
            ]);
        }

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

        // Check for existing submission in this edition
        // $existingSubmission = $entityManager->getRepository(Submission::class)
        //     ->findOneBy([
        //         'candidateProfile' => $candidateProfile,
        //         'editions' => $edition
        //     ]);

        // if ($existingSubmission) {
        //     return $this->redirectToRoute('application_confirmation', [
        //         'id' => $existingSubmission->getId(),
        //     ]);
        // }

        $form = $this->createForm(CandidateProfileType::class, $candidateProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('studentCardPath')->getData();

            if ($file) {
                $userId = $this->getUser()->getId();
                $userDir = $this->getParameter('upload_studencard') . '/' . $userId;
                !is_dir($userDir) && mkdir($userDir, 0755, true);

                $fileName = 'student_card.' . $file->guessExtension();
                $file->move($userDir, $fileName);
                $candidateProfile->setStudentCardPath('uploads/' . $userId . '/' . $fileName);
            }


            $submission = new Submission();
            $submission->setCandidateProfile($candidateProfile);
            $submission->setCurrentState('submitted');
            $submission->setIdentifier(bin2hex(random_bytes(5)));
            $submission->setIsSubmissionAccepted(false);
            $submission->setIsCandidateAccepted(false);
            $edition->addSubmission($submission);

            
            // Add the submission to the edition
            $submission->addEdition($edition);

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
            'edition' => $edition,
        ]);
    }

    #[Route('/apply/confirmation/{id}', name: 'application_confirmation')]
    public function confirmation(int $id, EntityManagerInterface $entityManager): Response
    {
        // Attempt to fetch the submission by ID
        $submission = $entityManager->getRepository(Submission::class)->find($id);

        // Check if the submission exists
        if (!$submission) {
            $this->addFlash('error', 'The requested submission does not exist.');
            return $this->redirectToRoute('app_apply_page'); // Redirect to an appropriate route
        }

        // Render the confirmation page with the submission
        return $this->render('apply/confirmation.html.twig', [
            'submission' => $submission,
        ]);
    }

    #[Route('/application/finish', name: 'app_apply_finish')]
    public function finish(Request $request): Response
    {
        // Validate user and get profiles
        $user = $this->getUser() ?? throw $this->createAccessDeniedException('Login required');
        $candidateProfile = $user->getUserProfile()?->getCandidateProfile() 
            ?? $this->redirectToRoute('app_home');
        $submission = $this->submissionRepository->findOneBy(['candidateProfile' => $candidateProfile])
            ?? throw $this->createNotFoundException('No active submission');

        // Create and handle form
        $subject = new SubjectStudy();
        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
            'method' => 'POST',
        ])
            ->add('subject', SubjectStudyType::class, ['data' => $subject])
            ->add('cv', CandidateCvType::class, ['data' => $candidateProfile])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle CV upload
                $cvFile = $form->get('cv')->get('CV')->getData();
                if ($cvFile) {
                    $userId = $user->getId();
                    $userCvDir = $this->getParameter('cv_directory') . '/' . $userId;
                    !is_dir($userCvDir) && mkdir($userCvDir, 0755, true);
                    
                    $cvFilename = 'cv.' . $cvFile->guessExtension();
                    $cvFile->move($userCvDir, $cvFilename);
                    $candidateProfile->setCV('uploads/cvs/' . $userId . '/' . $cvFilename);
                }

                // Handle video upload
                $videoFile = $form->get('subject')->get('videoPresantation')->getData();
                if ($videoFile) {
                    $userId = $user->getId();
                    $userVideoDir = $this->getParameter('video_directory') . '/' . $userId;
                    !is_dir($userVideoDir) && mkdir($userVideoDir, 0755, true);
                    
                    $videoFilename = 'video.' . $videoFile->guessExtension();
                    $videoFile->move($userVideoDir, $videoFilename);
                    $subject->setVideoPresantation('uploads/videos/' . $userId . '/' . $videoFilename);
                }

                // Set relationships and workflow
                $submission->setSubject($subject);
                $subject->setSubmission($submission);
                
                // Create workflow with initial state
                $workflow = new SubmissionWorkflow();
                $workflow->setSubmission($submission);
                $workflow->setState('under_review'); // Changed from 'under_review' to 'submitted'
                $submission->addSubmissionWorkflow($workflow);
                $submission->setCurrentState('under_review');

                // Persist changes
                $this->entityManager->persist($subject);
                $this->entityManager->persist($submission);
                $this->entityManager->persist($workflow);
                $this->entityManager->persist($candidateProfile);
                $this->entityManager->flush();

                $this->addFlash('success', 'Application submitted successfully.');
                return $this->redirectToRoute('application_confirmation', ['id' => $submission->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Debug form errors
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('apply/finish_submission.html.twig', [
            'form' => $form->createView(),
            'submission' => $submission,
        ]);
    }

    #[Route('/apply/submission_results/{id}', name: 'application_results')]
    public function applicationResults(
        int $id, 
        EntityManagerInterface $entityManager
    ): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        // Check if the submission exists
        if (!$submission) {
            $this->addFlash('error', 'The requested submission does not exist.');
            return $this->redirectToRoute('app_apply_page');
        }
        
        // Fetch all shared resources
        $resources = $entityManager->getRepository(SharedResource::class)->findAll();

        return $this->render('apply/results.html.twig', [
            'submission' => $submission,
            'resources' => $resources,
        ]);
    }
}
