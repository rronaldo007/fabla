<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WorkflowState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class UserWorkflowService {
    private WorkflowInterface $workflow;
    private EntityManagerInterface $em;

    public function __construct(
        WorkflowInterface $userRegistrationStateMachine,
        EntityManagerInterface $em
    ) {
        $this->workflow = $userRegistrationStateMachine;
        $this->em = $em;
    }
    public function applyTransition(User $user, string $transition, array $profileData = []): bool
    {
        if ($this->workflow->can($user, $transition)) {
            $this->workflow->apply($user, $transition);
            
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
}
