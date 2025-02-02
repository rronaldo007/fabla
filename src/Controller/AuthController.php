<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ResetPasswordType;
use App\Form\ForgotPasswordType;
use App\Entity\Role;
use App\Entity\WorkflowState;
use App\Service\UserWorkflowService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
                $this->addFlash('danger', 'Le mail est déjà utilisé.');
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
    
            $this->addFlash('success', 'Votre compte a été créé avec succès. Un email de confirmation vous a été envoyé.');
            return $this->redirectToRoute('app_login', ['id' => $user->getId()]);
        }
    
        return $this->render('auth/user_registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

     #[Route('/validate-email/{token}', name: 'app_validate_email')]
     public function validateEmail(
         string $token,
         EntityManagerInterface $em,
         UserWorkflowService $workflowService
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
             return $this->redirectToRoute('app_login');
         }
         
         return new Response('Invalid state for email validation.', Response::HTTP_BAD_REQUEST);
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

        if ($error) {
            $this->addFlash('danger', 'Identifiants invalides.');
        } else if ($this->getUser()) {
            $this->addFlash('success', 'Bienvenue ' . $this->getUser()->getEmail());
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
    public function forgotPassword(Request $request, EntityManagerInterface $em, \Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
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

                // Send Email with Reset Link
                $email = (new \Symfony\Component\Mime\Email())
                    ->from('no-reply@fablab.com')
                    ->to($user->getEmail())
                    ->subject('Password Reset Request')
                    ->html('<p>Click <a href="http://localhost:8000/reset-password/' . $resetToken . '">here</a> to reset your password.</p>');

                $mailer->send($email);

                $this->addFlash('success', 'Password reset link has been sent to your email.');
            } else {
                $this->addFlash('danger', 'No account found with this email.');
            }

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || $user->getResetPasswordTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('danger', 'Invalid or expired reset token.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('new_password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Passwords do not match.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setResetPasswordToken(null);
            $user->setResetPasswordTokenExpiresAt(null);

            $em->flush();

            $this->addFlash('success', 'Your password has been reset successfully.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in to change your password.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $oldPassword = $request->request->get('old_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify old password
            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', 'Incorrect current password.');
                return $this->redirectToRoute('app_change_password');
            }

            // Check if new passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'New passwords do not match.');
                return $this->redirectToRoute('app_change_password');
            }

            // Set new password
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Your password has been successfully changed.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('auth/change_password.html.twig');
    }

}
