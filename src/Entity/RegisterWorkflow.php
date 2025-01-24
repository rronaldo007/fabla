<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'register_workflows')]
class RegisterWorkflow
{
    public const DEFAULT_PLACES = [
        'new',
        'email_sent',
        'email_validated',
        'profile_completed',
        'active'
    ];

    public const DEFAULT_TRANSITIONS = [
        'send_email' => ['from' => 'new', 'to' => 'email_sent'],
        'validate_email' => ['from' => 'email_sent', 'to' => 'email_validated'],
        'complete_profile' => ['from' => 'email_validated', 'to' => 'profile_completed'],
        'activate_user' => ['from' => 'profile_completed', 'to' => 'active']
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $workflow_key;

    #[ORM\Column(type: 'json')]
    private array $places = self::DEFAULT_PLACES;

    #[ORM\Column(type: 'json')]
    private array $transitions = self::DEFAULT_TRANSITIONS;

    #[ORM\Column(length: 50)]
    private string $currentPlace = 'new';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'registerWorkflows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // getter + setter
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getWorkflowKey(): string
    {
        return $this->workflow_key;
    }

    public function setWorkflowKey(string $workflow_key): self
    {
        $this->workflow_key = $workflow_key;
        return $this;
    }

    public function getPlaces(): array
    {
        return $this->places;
    }

    public function setPlaces(array $places): self
    {
        $this->places = $places;
        return $this;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function setTransitions(array $transitions): self
    {
        $this->transitions = $transitions;
        return $this;
    }

    public function getCurrentPlace(): string
    {
        return $this->currentPlace;
    }

    public function setCurrentPlace(string $currentPlace): self
    {
        $this->currentPlace = $currentPlace;
        return $this;
    }

}