<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WorkflowState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserWorkflowService implements EventSubscriberInterface
{
    private WorkflowInterface $workflow;
    private EntityManagerInterface $em;
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $baseUrl;

    public function __construct(
        WorkflowInterface $userRegistrationStateMachine,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $baseUrl // This should receive the '%base_url%' parameter
    ) {
        $this->workflow = $userRegistrationStateMachine;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;
    }
    /**
     * Applies a workflow transition to the user.
     *
     * @param User   $user
     * @param string $transition
     *
     * @return bool
     *
     * @throws \LogicException if the transition cannot be applied.
     */
    public function applyTransition(User $user, string $transition): bool
    {
        if ($this->workflow->can($user, $transition)) {
            $this->workflow->apply($user, $transition);
            // We do not flush here so that the caller (controller) can flush all changes at once.
            return true;
        }

        throw new \LogicException(sprintf(
            'Cannot apply transition "%s" from state "%s".',
            $transition,
            $user->getCurrentPlace()
        ));
    }

    /**
     * Returns the events this subscriber is interested in.
     *
     * In this example, we listen to the event fired when the "send_email" transition is completed.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.user_registration.completed.send_email' => 'onSendEmail',
        ];
    }

    /**
     * This method is called when the "send_email" transition is completed.
     *
     * @param CompletedEvent $event
     */
    public function onSendEmail(CompletedEvent $event): void
    {
        $user = $event->getSubject();

        // Ensure that the subject is a User instance.
        if (!$user instanceof User) {
            return;
        }

        // Send the confirmation email.

        // Create and persist a new WorkflowState record.
        $workflowState = new WorkflowState();
        $workflowState->setState($user->getCurrentPlace());
        $workflowState->setUser($user);

        $this->em->persist($workflowState);

        // Note: Since your controller calls flush() after applying the transition,
        // you might remove this flush() here to avoid duplicate flushes.
        $this->em->flush();
    }

 
}
