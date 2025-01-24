<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private UserWorkflowService $workflowService;
    private EntityManagerInterface $em;

    public function __construct(UserWorkflowService $workflowService, EntityManagerInterface $em)
    {
        $this->workflowService = $workflowService;
        $this->em = $em;
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = new User();
        $user->setEmail($email)
             ->setPassword(password_hash($password, PASSWORD_BCRYPT));

        $this->em->persist($user);
        $this->workflowService->applyTransition($user, 'send_email');
        $this->em->flush();

        return new JsonResponse(['status' => 'User registered, email sent.']);
    }

    #[Route('/validate-email/{id}', name: 'app_validate_email', methods: ['GET'])]
    public function validateEmail(User $user): JsonResponse
    {
        $this->workflowService->applyTransition($user, 'validate_email');
        $this->em->flush();

        return new JsonResponse(['status' => 'Email validated.']);
    }

    #[Route('/complete-profile/{id}', name: 'app_complete_profile', methods: ['POST'])]
    public function completeProfile(Request $request, User $user): JsonResponse
    {
        $this->workflowService->applyTransition($user, 'complete_profile');
        $this->em->flush();

        return new JsonResponse(['status' => 'Profile completed.']);
    }

    #[Route('/activate/{id}', name: 'app_activate_user', methods: ['POST'])]
    public function activateUser(User $user): JsonResponse
    {
        $this->workflowService->applyTransition($user, 'activate_user');
        $this->em->flush();

        return new JsonResponse(['status' => 'User activated.']);
    }
}
