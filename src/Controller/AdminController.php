<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\SubmissionRepository;

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
        $submission = $submissionRepository->find($id);

        if (!$submission) {
            throw $this->createNotFoundException('Candidate not found.');
        }

        dd($submission);

        return $this->render('admin/candidate_profile.html.twig', [
            'submission' => $submission,
        ]);
    }


}
