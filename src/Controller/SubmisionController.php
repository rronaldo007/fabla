<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\SubmissionWorkflow;
use App\Repository\SubmissionRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SubmisionController extends AbstractController
{
    private $entityManager;
    private $emailService;
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
        $this->translator = $translator;
    }

    #[Route('/admin/submission/{id}/accept', name: 'admin_accept_submission')]
    public function acceptSubmission(int $id, Request $request): Response
    {
        // Check admin permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $submission = $this->entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException($this->translator->trans('submission.not_found'));
        }
        
        // Get additional comments that may have been provided
        $acceptanceComments = $request->request->get('acceptance_comments');
        
        // Change the submission status to accepted
        $submission->setIsSubmissionAccepted(true);
        $submission->setCurrentState('approved');
        
        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('approved');
        $workflow->setTransltionedAt(new \DateTime());
        
        
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();
        
        // Get the user to send email notification
        $user = $submission->getCandidateProfile()->getUserProfile()->getUser();
        
        // Send acceptance email notification to the candidate
        $this->emailService->sendApplicationAcceptedEmail($user, $submission);
        
        // Send notification to admin about the status change
        $this->emailService->sendAdminSubmissionStatusChangeEmail($submission, 'approved');
        
        $this->addFlash('success', $this->translator->trans('submission.accepted'));
        
        return $this->redirectToRoute('admin_candidates');
    }

    #[Route('/admin/submission/{id}/reject', name: 'admin_reject_submission')]
    public function rejectSubmission(int $id, Request $request): Response
    {
        // Check admin permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $submission = $this->entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException($this->translator->trans('submission.not_found'));
        }
        
        // Get rejection reason if provided
        $rejectionReason = $request->request->get('rejection_reason');
        
        // Change the submission status to rejected
        $submission->setIsSubmissionAccepted(false);
        $submission->setCurrentState('rejected');
        
        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('rejected');
        $workflow->setTransltionedAt(new \DateTime());
        
        
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();
        
        // Get the user to send email notification
        $user = $submission->getCandidateProfile()->getUserProfile()->getUser();
        
        // Send rejection email notification to the candidate
        $this->emailService->sendApplicationRejectedEmail($user, $submission, $rejectionReason);
        
        // Send notification to admin about the status change
        $this->emailService->sendAdminSubmissionStatusChangeEmail($submission, 'rejected');
        
        $this->addFlash('danger', $this->translator->trans('submission.rejected'));
        
        return $this->redirectToRoute('admin_candidates');
    }
    
    #[Route('/admin/submission/{id}/request-revision', name: 'admin_request_revision')]
    public function requestRevision(int $id, Request $request): Response
    {
        // Check admin permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $submission = $this->entityManager->getRepository(Submission::class)->find($id);
        
        if (!$submission) {
            throw $this->createNotFoundException($this->translator->trans('submission.not_found'));
        }
        
        // Get revision request details
        $revisionDetails = $request->request->get('revision_details');
        if (!$revisionDetails) {
            $this->addFlash('danger', $this->translator->trans('submission.revision_details_required'));
            return $this->redirectToRoute('admin_submission_view', ['id' => $id]);
        }
        
        // Change the submission status to needs_revision
        $submission->setCurrentState('needs_revision');
        
        // Log the workflow state
        $workflow = new SubmissionWorkflow();
        $workflow->setSubmission($submission);
        $workflow->setState('needs_revision');
        $workflow->setTransltionedAt(new \DateTime());
        
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();
        
        // Get the user to send email notification
        $user = $submission->getCandidateProfile()->getUserProfile()->getUser();
        
        // Send revision request email
        $this->emailService->sendApplicationRevisionRequestEmail($user, $submission, $revisionDetails);
        
        // Notify admin about the status change
        $this->emailService->sendAdminSubmissionStatusChangeEmail($submission, 'needs_revision');
        
        $this->addFlash('warning', $this->translator->trans('submission.revision_requested'));
        
        return $this->redirectToRoute('admin_candidates');
    }
}