<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\SubmissionRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SubmissionRepository $submissionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubmissionRepository $submissionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->submissionRepository = $submissionRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(SubmissionRepository $submissionRepository): Response
    {
        $user = $this->getUser();
        $userProfile = $user->getUserProfile();
        
        if ($userProfile && $userProfile->getCandidateProfile()) {
            $submissions = $this->submissionRepository
                ->findBy(['candidateProfile' => $userProfile->getCandidateProfile()]);
        } else {
            $submissions = [];
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'submissions' => $submissions,
        ]);
    }
}
