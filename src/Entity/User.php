<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $is_active = false;

    #[ORM\Column]
    private ?bool $is_validated = false;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserProfile::class, cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $currentPlace = 'new';

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WorkflowState::class)]
    private Collection $workflowStates;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emailValidationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailValidationTokenExpiresAt = null;

    public function __construct()
    {
        $this->workflowStates = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
    }

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetPasswordTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    private string $currentState;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'Jury')]
    private Collection $evaluations;

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function isValidated(): ?bool
    {
        return $this->is_validated;
    }

    public function setIsValidated(bool $is_validated): static
    {
        $this->is_validated = $is_validated;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

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

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): self
    {
        // Unset the owning side of the relation if necessary
        if ($userProfile === null && $this->userProfile !== null) {
            $this->userProfile->setUser(null);
        }

        // Set the owning side of the relation if necessary
        if ($userProfile !== null && $userProfile->getUser() !== $this) {
            $userProfile->setUser($this);
        }

        $this->userProfile = $userProfile;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return [$this->role->getName()];
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
     /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getWorkflowStates(): Collection
    {
        return $this->workflowStates;
    }

    public function addWorkflowState(WorkflowState $state): self
    {
        if (!$this->workflowStates->contains($state)) {
            $this->workflowStates->add($state);
            $state->setUser($this);
        }
        return $this;
    }

    public function getEmailValidationToken(): ?string
    {
        return $this->emailValidationToken;
    }

    public function setEmailValidationToken(?string $token): static
    {
        $this->emailValidationToken = $token;
        return $this;
    }

    public function getEmailValidationTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->emailValidationTokenExpiresAt;
    }

    public function setEmailValidationTokenExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->emailValidationTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    public function setCurrentState(string $state): self
    {
        $this->currentState = $state;
        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setJury($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getJury() === $this) {
                $evaluation->setJury(null);
            }
        }

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $token): static
    {
        $this->resetPasswordToken = $token;
        return $this;
    }

    public function getResetPasswordTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetPasswordTokenExpiresAt;
    }

    public function setResetPasswordTokenExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->resetPasswordTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function isResetPasswordTokenValid(): bool
    {
        return $this->resetPasswordTokenExpiresAt && new \DateTime() < $this->resetPasswordTokenExpiresAt;
    }
}

