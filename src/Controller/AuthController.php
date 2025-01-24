<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Entity\Role;
use App\Service\UserWorkflowService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function registration(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserWorkflowService $workflowService
     ): Response {
        $form = $this->createForm(UserType::class, new User());
        $form->handleRequest($request);
     
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            
            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('danger', 'le mail est déjà utilisé.');
                return $this->render('auth/user_registration.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
     
            $user = $form->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()))
                ->setIsActive(false)
                ->setIsValidated(false)
                ->setRole($em->getRepository(Role::class)->findOneBy(['name' => 'ROLE_CANDIDATE']));
     
            $em->persist($user);
            $workflowService->applyTransition($user, 'send_email');
            $em->flush();
     
            return $this->redirectToRoute('app_profile_completion', ['id' => $user->getId()]);
        }
     
        return $this->render('auth/user_registration.html.twig', [
            'form' => $form->createView(),
        ]);
     }

    #[Route('/profile-completion/{id}', name: 'app_profile_completion')]
    public function completeProfile(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService
    ): Response {
        // Ensure the user is in the correct state before proceeding
        if ($user->getCurrentPlace() !== 'email_validated') {
            return new Response('You must validate your email before completing your profile.', Response::HTTP_FORBIDDEN);
        }

        $profile = $user->getUserProfile() ?? new UserProfile();

        if (!$profile->getUser()) {
            $profile->setUser($user);
            $em->persist($profile);
        }

        $form = $this->createForm(UserProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workflowService->applyTransition($user, 'complete_profile');
            $em->flush();
            return $this->redirectToRoute('app_home');
        }

        return $this->render('auth/user_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/validate-email/{id}', name: 'app_validate_email')]
    public function validateEmail(
        int $id,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService
    ): Response {
        // Find the user by id
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            // Return a 404 response if the user is not found
            return new Response('User not found.', Response::HTTP_NOT_FOUND);
        }

        // Set the user as validated
        $user->setIsValidated(true);

        // Transition to the "email_validated" state
        if ($workflowService->applyTransition($user, 'validate_email')) {
            $em->flush();
            return new Response('Email validated successfully!');
        }

        return new Response('Invalid state for email validation.', Response::HTTP_BAD_REQUEST);
    }

}
