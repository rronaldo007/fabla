<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\SubmissionWorkflow;
use App\Repository\SubmissionRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SubmisionController extends AbstractController
{
    #[Route('/admin/submission/{id}/accept', name: 'admin_accept_submission')]
    public function acceptSubmission(int $id, EntityManagerInterface $entityManager): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException('Submission not found.');
        }

        // Change the submission status to accepted
        $submission->setIsSubmissionAccepted(true);
        $submission->setCurrentState('approved');

        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('approved');
        $workflow->setTransltionedAt(new \DateTime());

        $entityManager->persist($workflow);
        $entityManager->flush();

        $this->addFlash('success', 'Submission has been accepted.');
        return $this->redirectToRoute('admin_candidates');
    }


    #[Route('/admin/submission/{id}/reject', name: 'admin_reject_submission')]
    public function rejectSubmission(int $id, EntityManagerInterface $entityManager): Response
    {
        $submission = $entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException('Submission not found.');
        }

        // Change the submission status to rejected
        $submission->setIsSubmissionAccepted(false);
        $submission->setCurrentState('rejected');

        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('rejected');
        $workflow->setTransltionedAt(new \DateTime());

        $entityManager->persist($workflow);
        $entityManager->flush();

        $this->addFlash('danger', 'Submission has been rejected.');

        return $this->redirectToRoute('admin_candidates');
    }

}
