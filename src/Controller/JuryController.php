<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\JuryProfile;
use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Form\JuryFormType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/super-admin/jury')]
class JuryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/members', name: 'super_admin_list_jury')]
    public function listJuryMembers(): Response
    {
        $juryRole = $this->roleRepository->findOneBy(['name' => 'ROLE_JURY']);
        
        if (!$juryRole) {
            throw $this->createNotFoundException('Jury role not found');
        }

        $juryMembers = $this->userRepository
            ->createQueryBuilder('u')
            ->select('u', 'p')
            ->join('u.role', 'r')
            ->leftJoin('u.userProfile', 'p')
            ->where('r = :role')
            ->setParameter('role', $juryRole)
            ->getQuery()
            ->getResult();
        
            // dd($juryMembers);
        

        return $this->render('jury/jury_list.html.twig', [
            'juryMembers' => $juryMembers,
            'title' => 'Jury Members'
        ]);
    }

    #[Route('/new', name: 'super_admin_new_jury')]
    public function newJury(Request $request): Response
    {
        $jury = new User();
        $profile = new UserProfile();
        $juryProfile = new JuryProfile();
    
        $profile->setJuryProfile($juryProfile);
        $jury->setUserProfile($profile);
    
        $form = $this->createForm(JuryFormType::class, $jury, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $jury,
                $jury->getPassword()
            );
            $jury->setPassword($hashedPassword);
    
            $juryRole = $this->roleRepository->findOneBy(['name' => 'ROLE_JURY']);
            if (!$juryRole) {
                throw $this->createNotFoundException('Jury role not found');
            }
    
            $jury->setRole($juryRole);
            $jury->setIsActive(true);
            $jury->setIsValidated(true);
    
            // Handle CV upload
            $cvFile = $form->get('userProfile')->get('juryProfile')->get('miniCv')->getData();
            if ($cvFile) {
                $cvFileName = md5(uniqid()) . '.' . $cvFile->guessExtension();
                $cvFile->move($this->getParameter('cv_directory'), $cvFileName);
                $juryProfile->setMiniCv($cvFileName);
            }
    
            $this->entityManager->persist($jury);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Jury member added successfully.');
            return $this->redirectToRoute('super_admin_list_jury');
        }
    
        return $this->render('jury/jury_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Add New Jury Member'
        ]);
    }

    #[Route('/jury/{id}/edit', name: 'super_admin_edit_jury')]
    public function editJury(int $id, Request $request): Response
    {
        $jury = $this->userRepository->find($id);
        if (!$jury) {
            throw $this->createNotFoundException('Jury member not found.');
        }

        $form = $this->createForm(JuryFormType::class, $jury, [
            'validation_groups' => ['edit'],
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Jury member updated successfully.');
            return $this->redirectToRoute('super_admin_list_jury');
        }

        return $this->render('jury/edit_jury_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Jury Member'
        ]);
    }

    #[Route('/{id}/disable', name: 'super_admin_disable_jury')]
    public function disableJury(int $id): Response
    {
        $jury = $this->userRepository->find($id);
        if (!$jury) {
            throw $this->createNotFoundException('Jury member not found.');
        }

        $jury->setIsActive(false);
        $this->entityManager->flush();

        $this->addFlash('warning', 'Jury member has been disabled.');
        return $this->redirectToRoute('super_admin_list_jury');
    }

    #[Route('/{id}/enable', name: 'super_admin_enable_jury')]
    public function enableJury(int $id): Response
    {
        $jury = $this->userRepository->find($id);
        if (!$jury) {
            throw $this->createNotFoundException('Jury member not found.');
        }

        $jury->setIsActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Jury member has been enabled.');
        return $this->redirectToRoute('super_admin_list_jury');
    }

    #[Route('/{id}/archive', name: 'super_admin_archive_jury')]
    public function archiveJury(int $id): Response
    {
        $jury = $this->userRepository->find($id);
        if (!$jury) {
            throw $this->createNotFoundException('Jury member not found.');
        }

        $jury->getUserProfile()->setArchived(true);
        $jury->setIsActive(false);


        $this->entityManager->flush();

        $this->addFlash('danger', 'Jury member has been archived.');
        return $this->redirectToRoute('super_admin_list_jury');
    }


    #[Route('/{id}/restore', name: 'super_admin_restore_jury')]
    public function restoreJury(int $id): Response
    {
        $jury = $this->userRepository->find($id);
        if (!$jury) {
            throw $this->createNotFoundException('Jury member not found.');
        }

        $jury->getUserProfile()->setArchived(false);
        $jury->setIsActive(true); // Optionally re-enable them

        $this->entityManager->flush();

        $this->addFlash('success', 'Jury member has been restored.');
        return $this->redirectToRoute('super_admin_list_jury');
    }

}
