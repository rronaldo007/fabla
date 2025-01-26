<?php

namespace App\Service;

use App\Entity\Submission;
use App\Entity\SubmissionWorkflow;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\WorkflowInterface;

class SubmissionWorkflowService
{
    private WorkflowInterface $workflow;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(
        WorkflowInterface $submissionStateMachine,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        MailerInterface $mailer
    ) {
        $this->workflow = $submissionStateMachine;
        $this->em = $em;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    public function applyTransition(Submission $submission, string $transition): bool
    {
        if (!$this->workflow->can($submission, $transition)) {
            throw new \LogicException(sprintf(
                'Cannot apply transition "%s" from state "%s".',
                $transition,
                $submission->getCurrentState()
            ));
        }
    
        try {
            $this->em->beginTransaction();
            
            $this->workflow->apply($submission, $transition);
            
            $workflowEntry = new SubmissionWorkflow();
            $workflowEntry->setState($transition);
            $workflowEntry->setTransltionedAt(new \DateTime());
            
            $this->em->persist($submission);
            $this->em->persist($workflowEntry);
            
            $this->handlePostTransitionActions($submission, $transition);
            
            $this->em->flush();
            $this->em->commit();
            
            $this->logger->info('Transition applied', [
                'transition' => $transition,
                'submission_id' => $submission->getId(),
                'new_state' => $submission->getCurrentState()
            ]);
    
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function handlePostTransitionActions(Submission $submission, string $transition): void
    {
        switch ($transition) {
            case 'submitted':
                $this->sendEmail(
                    $submission->getCandidateProfile()->getUserProfile()->getUser()->getEmail(),
                    'Submission Submitted',
                    '<p>Your submission has been successfully submitted and is now under review.</p>'
                );
                break;
    
            case 'under_review':
                $this->logger->info(sprintf(
                    'Submission ID "%d" is now under review.',
                    $submission->getId()
                ));
                break;
    
            case 'approve':
                $this->sendEmail(
                    $submission->getCandidateProfile()->getUserProfile()->getUser()->getEmail(),
                    'Submission Approved',
                    '<p>Congratulations! Your submission has been approved.</p>'
                );
                break;
    
            case 'reject':
                $this->sendEmail(
                    $submission->getCandidateProfile()->getUserProfile()->getUser()->getEmail(),
                    'Submission Rejected',
                    '<p>We regret to inform you that your submission has been rejected.</p>'
                );
                break;
    
            default:
                $this->logger->info(sprintf(
                    'No additional actions configured for transition "%s".',
                    $transition
                ));
                break;
        }
    }

    private function sendEmail(string $to, string $subject, string $html): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@yourdomain.com')
                ->to($to)
                ->subject($subject)
                ->html($html);

            $this->mailer->send($email);

            $this->logger->info('Email sent successfully to ' . $to);
            return true;
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to send email to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }
}
