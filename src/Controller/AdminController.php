<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\Submission;
use App\Repository\SubmissionRepository;
use App\Entity\SubmissionWorkflow;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        // Fetch all submissions
        $submissions = $this->entityManager->getRepository(Submission::class)->findAll();

        return $this->render('admin/candidates_list.html.twig', [
            'submissions' => $submissions,
        ]);
    }

    #[Route('/admin/selected_candidates', name: 'admin_selected_candidates')]
    public function selectedCandidates(SubmissionRepository $submissionRepository): Response
    {
        // Fetch candidates whose submissions are accepted
        $submissions = $submissionRepository->findBy([
            'isCandidateAccepted' => true,
        ]);

        return $this->render('admin/selected_candidates.html.twig', [
            'submissions' => $submissions,
        ]);
    }

    #[Route('/admin/candidate/{id}', name: 'admin_view_candidate')]
    public function viewCandidate(int $id, SubmissionRepository $submissionRepository): Response
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

        $submission = $query->getSingleResult();

        // dd($submission);

        if (!$submission) {
            throw $this->createNotFoundException('Candidate not found.');
        }

        return $this->render('admin/candidate_profile.html.twig', [
            'submission' => $submission,
        ]);
    }

    #[Route('/admin/submission/{id}/accept', name: 'admin_accept_submission')]
    public function acceptSubmission(int $id, EntityManagerInterface $entityManager): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException('Submission not found.');
        }

        // Change the submission status to accepted
        $submission->setIsSubmissionAccepted(true);
        $submission->setCurrentState('approved');

        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('approved');
        $workflow->setTransltionedAt(new \DateTime());

        $entityManager->persist($workflow);
        $entityManager->flush();

        $this->addFlash('success', 'Submission has been accepted.');

        return $this->redirectToRoute('admin_candidates');
    }

    #[Route('/admin/submission/{id}/reject', name: 'admin_reject_submission')]
    public function rejectSubmission(int $id, EntityManagerInterface $entityManager): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException('Submission not found.');
        }

        // Change the submission status to rejected
        $submission->setIsSubmissionAccepted(false);
        $submission->setCurrentState('rejected');

        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('rejected');
        $workflow->setTransltionedAt(new \DateTime());

        $entityManager->persist($workflow);
        $entityManager->flush();

        $this->addFlash('danger', 'Submission has been rejected.');

        return $this->redirectToRoute('admin_candidates');
    }





}
