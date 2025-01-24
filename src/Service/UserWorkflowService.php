<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class UserWorkflowService
{
    private WorkflowInterface $workflow;
    private EntityManagerInterface $em;

    public function __construct(WorkflowInterface $userRegistrationStateMachine, EntityManagerInterface $em)
    {
        $this->workflow = $userRegistrationStateMachine;
        $this->em = $em;
    }

    /**
     * Apply a workflow transition to the given user.
     *
     * @param User   $user       The user entity
     * @param string $transition The transition to apply
     * @param array  $profileData Optional profile data for the `complete_profile` transition
     *
     * @return bool True if the transition was successfully applied, false otherwise
     */
    public function applyTransition(User $user, string $transition, array $profileData = []): bool
    {
        // Check if the transition is valid for the current state
        if ($this->workflow->can($user, $transition)) {
            // Apply the transition
            $this->workflow->apply($user, $transition);

            return true;
        }

        // Transition is invalid; throw an exception or return false
        throw new \LogicException(sprintf(
            'Cannot apply transition "%s" from the current state "%s".',
            $transition,
            $user->getCurrentPlace()
        ));
    }

    /**
     * Get all available transitions for the given user.
     *
     * @param User $user
     * @return array
     */
    public function getAvailableTransitions(User $user): array
    {
        return $this->workflow->getEnabledTransitions($user);
    }

}
