<?php

namespace App\Controller;

use App\Entity\SharedResource;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class ResourceController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/resources', name: 'super_admin_resources')]
    public function listResources(): Response
    {
        $resources = $this->entityManager->getRepository(SharedResource::class)->findAll();

        return $this->render('resource/resources_list.html.twig', [
            'resources' => $resources,
        ]);
    }

    #[Route('resource/new', name: 'super_admin_new_resource')]
    public function newResource(Request $request): Response
    {
        $resource = new SharedResource();

        $form = $this->createFormBuilder($resource)
            ->add('name', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('brandModel', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('commissioningDate', DateType::class, ['widget' => 'single_text', 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->add('save', SubmitType::class, ['label' => 'Add Resource', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($resource);
            $this->entityManager->flush();

            $this->addFlash('success', 'Resource added successfully.');
            return $this->redirectToRoute('super_admin_resources');
        }

        return $this->render('resource/resource_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('resource/{id}/edit', name: 'super_admin_edit_resource')]
    public function editResource(int $id, Request $request): Response
    {
        $resource = $this->entityManager->getRepository(SharedResource::class)->find($id);
        if (!$resource) {
            throw $this->createNotFoundException('Resource not found.');
        }

        $form = $this->createFormBuilder($resource)
            ->add('name', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('brandModel', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('commissioningDate', DateType::class, ['widget' => 'single_text', 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->add('save', SubmitType::class, ['label' => 'Update Resource', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Resource updated successfully.');
            return $this->redirectToRoute('super_admin_resources');
        }

        return $this->render('resource/resource_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('resource/{id}/archive', name: 'super_admin_archive_resource')]
    public function archiveResource(int $id): Response
    {
        $resource = $this->entityManager->getRepository(SharedResource::class)->find($id);
        if (!$resource) {
            throw $this->createNotFoundException('Resource not found.');
        }

        $resource->setArchived(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Resource archived successfully.');
        return $this->redirectToRoute('super_admin_resources');
    }

    #[Route('resource/archived', name: 'super_admin_archived_resources')]
    public function listArchivedResources(): Response
    {
        $resources = $this->entityManager->getRepository(SharedResource::class)->findBy(['isArchived' => true]);

        return $this->render('resource/resource_archived_list.html.twig', [
            'resources' => $resources,
        ]);
    }

    #[Route('/{id}/restore', name: 'super_admin_restore_resource')]
    public function restoreResource(int $id): Response
    {
        $resource = $this->entityManager->getRepository(SharedResource::class)->find($id);
        if (!$resource) {
            throw $this->createNotFoundException('Resource not found.');
        }

        $resource->setArchived(false);
        $this->entityManager->flush();

        $this->addFlash('success', 'Resource reactivated successfully.');
        return $this->redirectToRoute('super_admin_archived_resources');
    }

}
