<?php

namespace App\Controller;

use App\Entity\UserProfile;
use App\Entity\WorkflowState;
use App\Form\UserProfileType;
use App\Service\UserWorkflowService;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile/{id<\d+>}', name: 'app_profile')]
    public function index(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $userProfile = $em->getRepository(UserProfile::class)->find($id);
        
        if (!$userProfile || $user->getId() !== $userProfile->getUser()->getId()) {
            return $this->redirectToRoute('app_four_o_four');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profile' => $userProfile,
            'candidateProfile' => $userProfile->getCandidateProfile()
        ]);
    }

    #[Route('/profile-completion/{id}', name: 'app_profile_completion')]
    public function completeProfile(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserWorkflowService $workflowService
     ): Response {
     
        // Store initial state
        $initialState = new WorkflowState();
        $initialState->setState($user->getCurrentPlace());
        $initialState->setUser($user);
        $em->persist($initialState);
     
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
            $this->addFlash('success', 'Votre profil a été completé avec succès.');
            return $this->redirectToRoute('app_home');
        }
     
        return $this->render('auth/user_profile.html.twig', [
            'form' => $form->createView(),
        ]);
     }
}
