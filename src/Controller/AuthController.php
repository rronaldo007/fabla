<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
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
                $this->addFlash('danger', 'le mail est déjà utilisé.');
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
    
            $workflowState = new WorkflowState();
            $workflowState->setState('new');
            $workflowState->setUser($user);
            
            $em->persist($user);
            $em->persist($workflowState);
    
            $workflowService->applyTransition($user, 'send_email');
    
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

}
