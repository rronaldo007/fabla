<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\RegisterWorkflow;
use App\Form\UserType;
use App\Form\UserProfileType;
use App\Service\UserWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    #[Route('/registration', name: 'app_registration')]
    public function registration(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserWorkflowService $workflowService
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $user->getEmail();

            // Check if user with this email already exists
            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('danger', 'This email is already in use.');
                return $this->render('auth/user_registration.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            // Assign default role
            $role = $em->getRepository(Role::class)->findOneBy(['name' => 'ROLE_CANDIDATE']);
            $user->setRole($role);

            // Generate validation token (optional)
            $user->generateValidationToken();

            // Persist the user first
            $em->persist($user);
            $em->flush();

            // 1) Create a RegisterWorkflow entry for this user
            $registerWorkflow = new RegisterWorkflow();
            $registerWorkflow
                ->setWorkflowKey('user_registration')  // or any unique key or name
                ->setName('User Registration Flow');

            $em->persist($registerWorkflow);
            $em->flush();

            // 2) Trigger "send_email" on the new RegisterWorkflow
            $workflowService->applyTransition($registerWorkflow, 'send_email');
            $em->flush();

            // 3) Feedback
            $this->addFlash('success', 'Registration successful! Please check your inbox to validate your email.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/user_registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/validate-email', name: 'app_validate_email')]
    public function validateEmail(
        Request $request,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService
    ): Response {
        $token = $request->query->get('token');
        if (!$token) {
            return $this->render('auth/email_validation_error.html.twig', [
                'error' => 'No token provided.',
            ]);
        }

        // 1) Find user by token
        $user = $em->getRepository(User::class)->findOneBy(['validationToken' => $token]);
        if (!$user) {
            return $this->render('auth/email_validation_error.html.twig', [
                'error' => 'Invalid or expired validation link.',
            ]);
        }

        // 2) Find this user's RegisterWorkflow
        $registerWorkflow = $em->getRepository(RegisterWorkflow::class)->findOneBy([
            'user' => $user,
            'workflow_key' => 'user_registration',
        ]);

        if (!$registerWorkflow) {
            return $this->render('auth/email_validation_error.html.twig', [
                'error' => 'No workflow found for this user.',
            ]);
        }

        // 3) Attempt the "validate_email" transition
        if ($workflowService->applyTransition($registerWorkflow, 'validate_email')) {
            $user->setValidationToken(null); // can't reuse

            $em->flush();

            $this->addFlash('success', 'Your email has been validated successfully!');
            return $this->redirectToRoute('app_profile_completion', ['id' => $user->getId()]);
        }

        return $this->render('auth/email_validation_error.html.twig', [
            'error' => 'User not in a valid state for email validation.',
        ]);
    }

    #[Route('/profile-completion/{id}', name: 'app_profile_completion')]
    public function completeProfile(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService
    ): Response {
        // 1) Retrieve the RegisterWorkflow for this user
        $registerWorkflow = $em->getRepository(RegisterWorkflow::class)->findOneBy([
            'user' => $user,
            'workflow_key' => 'user_registration',
        ]);

        if (!$registerWorkflow) {
            $this->addFlash('warning', 'No registration workflow found.');
            return $this->redirectToRoute('app_registration');
        }

        // 2) Check if the user is indeed "email_validated"
        if ($registerWorkflow->getCurrentPlace() !== 'email_validated') {
            $this->addFlash('warning', 'You must validate your email before completing your profile.');
            return $this->redirectToRoute('app_registration');
        }

        // Build or retrieve the user profile
        $profile = $user->getUserProfile() ?? new UserProfile();
        if (!$profile->getUser()) {
            $profile->setUser($user);
            $em->persist($profile);
        }

        $form = $this->createForm(UserProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3) Fire "complete_profile"
            $workflowService->applyTransition($registerWorkflow, 'complete_profile');
            $em->flush();

            $this->addFlash('success', 'Profile completed successfully! You can now continue to the homepage.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('auth/user_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            $this->addFlash('info', 'You are already logged in.');
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // If there's a login error, check workflow
        if ($lastUsername && $error) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $lastUsername]);

            if ($user) {
                $registerWorkflow = $em->getRepository(RegisterWorkflow::class)->findOneBy([
                    'user' => $user,
                    'workflow_key' => 'user_registration'
                ]);

                // If the user hasn't validated email, direct them to "need-validation"
                if ($registerWorkflow && $registerWorkflow->getCurrentPlace() !== 'email_validated') {
                    return new RedirectResponse($this->urlGenerator->generate('app_needs_validation'));
                }
            }
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method should not be called directly.');
    }

    #[Route('/need-validation', name: 'app_needs_validation')]
    public function needValidation(): Response
    {
        return $this->render('auth/validation.html.twig', [
            'message' => 'Please validate your email before logging in.'
        ]);
    }
}
