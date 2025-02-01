<?php

namespace App\Controller;

use App\Entity\Edition;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class EditionController extends AbstractController
{
    #[Route('/admin/editions', name: 'admin_editions')]
    public function listEditions(EntityManagerInterface $entityManager): Response
    {
        $editions = $entityManager->getRepository(Edition::class)->findAll();

        return $this->render('edition/editions_list.html.twig', [
            'editions' => $editions,
        ]);
    }

    #[Route('/admin/editions/{id}/edit', name: 'admin_edit_edition')]
    public function editEdition(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $edition = $entityManager->getRepository(Edition::class)->find($id);

        if (!$edition) {
            throw $this->createNotFoundException('Édition non trouvée.');
        }

        $form = $this->createFormBuilder($edition)
            ->add('year', IntegerType::class)
            ->add('startPublication', DateTimeType::class)
            ->add('startApplication', DateTimeType::class)
            ->add('endApplication', DateTimeType::class)
            ->add('announcementDate', DateTimeType::class)
            ->add('isCurrent', CheckboxType::class, ['required' => false])
            ->add('save', SubmitType::class, ['label' => 'Modifier l\'édition'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Édition modifiée avec succès.');

            return $this->redirectToRoute('admin_editions');
        }

        return $this->render('edition/edition_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/editions/{id}/archive', name: 'admin_archive_edition')]
    public function archiveEdition(int $id, EntityManagerInterface $entityManager): Response
    {
        $edition = $entityManager->getRepository(Edition::class)->find($id);

        if (!$edition) {
            throw $this->createNotFoundException('Édition non trouvée.');
        }

        $edition->setIsCurrent(false); // Marque comme non actuelle ou archivée
        $entityManager->flush();

        $this->addFlash('success', 'Édition archivée avec succès.');

        return $this->redirectToRoute('admin_editions');
    }

    #[Route('/admin/editions/new', name: 'admin_new_edition')]
    public function newEdition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $edition = new Edition();

        $form = $this->createFormBuilder($edition)
            ->add('year', IntegerType::class)
            ->add('startPublication', DateTimeType::class)
            ->add('startApplication', DateTimeType::class)
            ->add('endApplication', DateTimeType::class)
            ->add('announcementDate', DateTimeType::class)
            ->add('isCurrent', CheckboxType::class, ['required' => false])
            ->add('save', SubmitType::class, ['label' => 'Créer une édition'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($edition);
            $entityManager->flush();

            $this->addFlash('success', 'Édition ajoutée avec succès.');

            return $this->redirectToRoute('admin_editions');
        }

        return $this->render('edition/new_edition_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
