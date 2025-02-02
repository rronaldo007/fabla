<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/jury/evaluation')]
#[IsGranted('ROLE_JURY')] // Sécurité : accès réservé aux jurys
final class EvaluationController extends AbstractController
{
    #[Route('/noter/{id}', name: 'jury_noter_submission', methods: ['GET', 'POST'])]
    public function noter(
        Submission $submission,
        Request $request,
        EntityManagerInterface $entityManager,
        EvaluationRepository $evaluationRepository
    ): Response {
        $jury = $this->getUser();
        $candidat = $submission->getCandidateProfile(); // Utilisation correcte de la relation

        $evaluation = new Evaluation();
        $evaluation->setJury($jury);
        $evaluation->setCandidat($candidat);
        $evaluation->setSubmission($submission);
        $evaluation->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre notation a été enregistrée.');
            return $this->redirectToRoute('admin_view_candidate', ['id'=> $submission->getId()]);
        }

        return $this->render('evaluation/noter.html.twig', [
            'submission' => $submission,
            'candidat' => $candidat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/list', name: 'jury_liste_submissions', methods: ['GET'])]
    public function listeSubmissions(EvaluationRepository $evaluationRepository): Response
    {
        $jury = $this->getUser();
        $submissions = $evaluationRepository->findSubmissionsForJury($jury);

        return $this->render('evaluation/liste.html.twig', [
            'submissions' => $submissions,
        ]);
    }
}
