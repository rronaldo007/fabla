<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ResetPasswordType;
use App\Form\ForgotPasswordType;
use App\Entity\Role;
use App\Entity\WorkflowState;
use App\Service\UserWorkflowService;
use App\Service\EmailService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AuthController extends AbstractController
{
    private $emailService;
    private $translator;
    
    public function __construct(EmailService $emailService, TranslatorInterface $translator)
    {
        $this->emailService = $emailService;
        $this->translator = $translator;
    }

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
                $this->addFlash('danger', $this->translator->trans('auth.email.already_used'));
                return $this->render('auth/user_registration.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            $user = $form->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()))
                ->setIsActive(false)
                ->setIsValidated(false)
                ->setRole($em->getRepository(Role::class)->findOneBy(['name' => 'ROLE_CANDIDATE']))
                ->setEmailValidationToken(bin2hex(random_bytes(32)))
                ->setEmailValidationTokenExpiresAt(new \DateTime('+24 hours'));
    
            $em->persist($user);
    
            // Apply the workflow transition which will trigger the onSendEmail() event.
            $workflowService->applyTransition($user, 'send_email');
    
            // Flush all changes (user and workflow state)
            $em->flush();
            
            // Send confirmation email using the service
            $this->emailService->sendRegistrationConfirmationEmail($user);
    
            $this->addFlash('success', $this->translator->trans('auth.registration.success'));
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('auth/user_registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/validate-email/{token}', name: 'app_validate_email')]
    public function validateEmail(
        string $token,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService,
        EmailService $emailService
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['emailValidationToken' => $token]);
        
        if (!$user) {
            return new Response('Invalid validation token.', Response::HTTP_NOT_FOUND);
        }
        
        if ($user->getEmailValidationTokenExpiresAt() < new \DateTime()) {
            return new Response('Validation token has expired.', Response::HTTP_GONE);
        }
    
        $initialState = new WorkflowState();
        $initialState->setState($user->getCurrentPlace());
        $initialState->setUser($user);
        $em->persist($initialState);
        
        $user->setIsValidated(true)
            ->setEmailValidationToken(null)
            ->setEmailValidationTokenExpiresAt(null);
        
        if ($workflowService->applyTransition($user, 'validate_email')) {
            $em->flush();
            $emailService->sendEmailValidationSuccessNotification($user);
            return $this->redirectToRoute('app_login');
        }
        
        return new Response('Invalid state for email validation.', Response::HTTP_BAD_REQUEST);
    }

    #[Route('/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->getUser()) {
            $this->addFlash('info', $this->translator->trans('auth.already_logged_in'));
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $this->addFlash('danger', $this->translator->trans('auth.invalid_credentials'));
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

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetPasswordToken($resetToken);
                $user->setResetPasswordTokenExpiresAt(new \DateTime('+1 hour'));

                $em->flush();

                // Send the password reset email using the dedicated service
                $this->emailService->sendPasswordResetEmail($user);

                $this->addFlash('success', $this->translator->trans('auth.password.reset_link_sent'));
            } else {
                // Don't reveal whether a user exists to prevent enumeration attacks
                $this->addFlash('info', $this->translator->trans('auth.password.check_email_for_instructions'));
            }

            // Always redirect to avoid timing attacks
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token, 
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || $user->getResetPasswordTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('danger', $this->translator->trans('auth.password.invalid_reset_token'));
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('new_password')->getData();

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setResetPasswordToken(null);
            $user->setResetPasswordTokenExpiresAt(null);

            $em->flush();
            
            // Send password changed confirmation
            $this->emailService->sendPasswordChangedConfirmation($user);

            $this->addFlash('success', $this->translator->trans('auth.password.reset_success'));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('danger', $this->translator->trans('auth.login_required'));
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $oldPassword = $request->request->get('old_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify old password
            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', $this->translator->trans('auth.password.incorrect_current'));
                return $this->redirectToRoute('app_change_password');
            }

            // Check if new passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', $this->translator->trans('auth.password.passwords_mismatch'));
                return $this->redirectToRoute('app_change_password');
            }

            // Set new password
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();
            
            // Send password changed confirmation
            $this->emailService->sendPasswordChangedConfirmation($user);

            $this->addFlash('success', $this->translator->trans('auth.password.change_success'));
            return $this->redirectToRoute('app_home');
        }

        return $this->render('auth/change_password.html.twig');
    }

    #[Route('/resend-verification-email', name: 'app_resend_verification')]
    public function resendVerificationEmail(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ForgotPasswordType::class); // Reusing the same form type
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && !$user->getIsValidated()) {
                // Generate a new token
                $user->setEmailValidationToken(bin2hex(random_bytes(32)))
                    ->setEmailValidationTokenExpiresAt(new \DateTime('+24 hours'));

                $em->flush();

                // Send the verification email
                $this->emailService->sendRegistrationConfirmationEmail($user);

                $this->addFlash('success', $this->translator->trans('auth.email.verification_resent'));
            } else {
                // Don't reveal specific information
                $this->addFlash('info', $this->translator->trans('auth.email.check_inbox'));
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/resend_verification.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}