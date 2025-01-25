<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WorkflowState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class UserWorkflowService {
    private WorkflowInterface $workflow;
    private EntityManagerInterface $em;
    private MailerInterface $mailer;

    private LoggerInterface $logger;

    public function __construct(
        WorkflowInterface $userRegistrationStateMachine,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->workflow = $userRegistrationStateMachine;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }
    public function applyTransition(User $user, string $transition, array $profileData = []): bool
    {
        if ($this->workflow->can($user, $transition)) {
            $this->workflow->apply($user, $transition);

            // Send email if the transition is `send_email`
            if ($transition === 'send_email') {
                $this->sendEmail($user);
            }

            // Create workflow state record
            $workflowState = new WorkflowState();
            $workflowState->setState($user->getCurrentPlace());
            $workflowState->setUser($user);

            $this->em->persist($workflowState);
            $this->em->flush();

            return true;
        }

        throw new \LogicException(sprintf(
            'Cannot apply transition "%s" from state "%s".',
            $transition,
            $user->getCurrentPlace()
        ));
    }

    private function sendEmail(User $user): bool
    {
        try {
            $email = (new Email())
                ->from('no-reply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Confirm Your Registration')
                ->html('<p>Please confirm your email by clicking the link below:</p>
                        <a href="https://localhost:8000/validate-email/' . $user->getEmailValidationToken() . '">Confirm Email</a>');

            $this->mailer->send($email);

            $this->logger->info('Email sent successfully to ' . $user->getEmail());
            return true;
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to send email to ' . $user->getEmail() . ': ' . $e->getMessage());
            return false;
        }
    }
}
