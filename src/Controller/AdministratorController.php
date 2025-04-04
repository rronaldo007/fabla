<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Form\AdministratorFormType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/super-admin/admins')]
class AdministratorController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/administrators', name: 'super_admin_list_admins')]
    public function listAdmins(): Response
    {
        $adminRole = $this->roleRepository->findOneBy(['name' => 'ROLE_ADMIN']);
        
        if (!$adminRole) {
            throw $this->createNotFoundException('Admin role not found');
        }

        $admins = $this->userRepository
            ->createQueryBuilder('u')
            ->select('u', 'p')
            ->join('u.role', 'r')
            ->leftJoin('u.userProfile', 'p')
            ->where('r = :role')
            ->setParameter('role', $adminRole)
            ->getQuery()
            ->getResult();

        return $this->render('administrator/admin_list.html.twig', [
            'admins' => $admins,
            'title' => 'Administrator List'
        ]);
    }

    #[Route('/administrators/new', name: 'super_admin_new_admin')]
    public function newAdmin(Request $request): Response
    {
        $admin = new User();
        $profile = new UserProfile();
        $admin->setUserProfile($profile);

        $form = $this->createForm(AdministratorFormType::class, $admin, [
            'is_edit'=> false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $admin,
                $admin->getPassword()
            );
            $admin->setPassword($hashedPassword);

            $adminRole = $this->roleRepository->findOneBy(['name' => 'ROLE_ADMIN']);
            if (!$adminRole) {
                throw $this->createNotFoundException('Admin role not found');
            }
            
            $admin->setRole($adminRole);
            $admin->setIsActive(true);
            $admin->setIsValidated(true);
            $admin->setCurrentPlace('Active');

            $this->entityManager->persist($admin);
            $this->entityManager->flush();

            $this->addFlash('success', 'Admin added successfully.');
            return $this->redirectToRoute('super_admin_list_admins');
        }

        return $this->render('administrator/admin_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Add New Administrator'
        ]);
    }

    #[Route('/administrators/{id}/edit', name: 'super_admin_edit_admin')]
    public function editAdmin(int $id, Request $request): Response
    {
        $admin = $this->userRepository->find($id);
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        $form = $this->createForm(AdministratorFormType::class, $admin, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            if (!empty($password)) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $admin,
                    $password
                );
                $admin->setPassword($hashedPassword);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Administrator updated successfully.');
            return $this->redirectToRoute('super_admin_list_admins');
        }

        return $this->render('administrator/admin_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Administrator'
        ]);
    }

    #[Route('/administrators/{id}/disable', name: 'super_admin_disable_admin')]
    public function disableAdmin(int $id): Response
    {
        $admin = $this->userRepository->find($id);
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        $admin->setIsActive(false);
        $admin->setCurrentPlace("Disabled");
        $this->entityManager->flush();

        $this->addFlash('warning', 'Administrator has been disabled.');
        return $this->redirectToRoute('super_admin_list_admins');
    }

    #[Route('/administrators/{id}/enable', name: 'super_admin_enable_admin')]
    public function enableAdmin(int $id): Response
    {
        $admin = $this->userRepository->find($id);
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        $admin->setIsActive(true);
        $admin->setCurrentPlace('Active');
        $this->entityManager->flush();

        $this->addFlash('success', 'Administrator has been enabled.');
        return $this->redirectToRoute('super_admin_list_admins');
    }

    #[Route('/administrators/{id}/archive', name: 'super_admin_archive_admin')]
    public function archiveAdmin(int $id): Response
    {
        $admin = $this->userRepository->find($id);
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        $admin->getUserProfile()->setArchived(true);
        $admin->setIsActive(false); // Auto-disable when archived
        $this->entityManager->flush();

        $this->addFlash('danger', 'Administrator has been archived.');
        return $this->redirectToRoute('super_admin_list_admins');
    }

    #[Route('/administrators/{id}/restore', name: 'super_admin_restore_admin')]
    public function restoreAdmin(int $id): Response
    {
        $admin = $this->userRepository->find($id);
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        $admin->getUserProfile()->setArchived(false);
        $this->entityManager->flush();

        $this->addFlash('success', 'Administrator has been restored.');
        return $this->redirectToRoute('super_admin_list_admins');
    }
}
