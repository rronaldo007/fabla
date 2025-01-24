<?php

namespace App\Service;

use App\Entity\RegisterWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Exception\TransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

class UserWorkflowService
{
    public function __construct(
        private WorkflowInterface $userRegistrationStateMachine,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function applyTransition(RegisterWorkflow $workflow, string $transition, array $context = []): bool
    {
        try {
            if (!$this->userRegistrationStateMachine->can($workflow, $transition)) {
                throw new TransitionException(
                    $workflow,
                    $transition,
                    $this->userRegistrationStateMachine,
                    sprintf(
                        'Cannot apply transition "%s" from state "%s".',
                        $transition,
                        $workflow->getCurrentPlace()
                    )
                );
            }

            // Pre-transition tasks
            $this->executePreTransitionTasks($workflow, $transition, $context);

            // Apply the transition
            $this->userRegistrationStateMachine->apply($workflow, $transition, $context);

            // Post-transition tasks
            $this->executePostTransitionTasks($workflow, $transition, $context);

            // Persist changes
            $this->entityManager->persist($workflow);
            $this->entityManager->flush();

            // Log
            $this->logger->info('Workflow transition applied successfully', [
                'workflow_id' => $workflow->getId(),
                'transition'  => $transition,
                'new_state'   => $workflow->getCurrentPlace()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Workflow transition failed', [
                'workflow_id' => $workflow->getId(),
                'transition'  => $transition,
                'error'       => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAvailableTransitions(RegisterWorkflow $workflow): array
    {
        return array_map(
            fn($transition) => $transition->getName(),
            $this->userRegistrationStateMachine->getEnabledTransitions($workflow)
        );
    }

    private function executePreTransitionTasks(RegisterWorkflow $workflow, string $transition, array $context): void
    {
        match($transition) {
            'send_email'       => $this->sendVerificationEmail($workflow),
            'validate_email'   => $this->validateVerificationToken($workflow, $context),
            'complete_profile' => $this->validateProfileData($context),
            'activate_user'    => $this->checkActivationRequirements($workflow, $context),
            default => null,
        };
    }

    private function executePostTransitionTasks(RegisterWorkflow $workflow, string $transition, array $context): void
    {
        match($transition) {
            'complete_profile' => $this->updateUserProfile($workflow, $context),
            'activate_user'    => $this->finalizeUserActivation($workflow, $context),
            default => null,
        };
    }

    private function sendVerificationEmail(RegisterWorkflow $workflow): void
    {
    }

    private function validateVerificationToken(RegisterWorkflow $workflow, array $context): void
    {
        // ...
    }

    private function validateProfileData(array $context): void
    {
        // ...
    }

    private function checkActivationRequirements(RegisterWorkflow $workflow, array $context): void
    {
        // ...
    }

    private function updateUserProfile(RegisterWorkflow $workflow, array $context): void
    {
        // ...
    }

    private function finalizeUserActivation(RegisterWorkflow $workflow, array $context): void
    {
    }

}
