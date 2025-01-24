<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_active = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $is_validated = false;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserProfile::class, cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    /**
     * Stores the current workflow state for this user (e.g. "new", "email_sent", "email_validated", etc.)
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $currentPlace = 'new';

    /**
     * A random token for email verification (nullable because it may not exist after validation).
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $validationToken = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $email_validated = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RegisterWorkflow::class, cascade: ['persist', 'remove'])]
    private Collection $registerWorkflows;

    public function __construct()
    {
        $this->registerWorkflows = new ArrayCollection();
    }

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

    public function getCurrentPlace(): string
    {
        return $this->currentPlace;
    }

    public function setCurrentPlace(string $currentPlace): self
    {
        $this->currentPlace = $currentPlace;
        return $this;
    }

    /**
     * Returns the user's roles (e.g., ["ROLE_CANDIDATE"]).
     */
    public function getRoles(): array
    {
        return [$this->role->getName()];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase right now
    }

    /**
     * Get the current validation token (if any).
     */
    public function getValidationToken(): ?string
    {
        return $this->validationToken;
    }

    /**
     * Set the validation token manually.
     */
    public function setValidationToken(?string $validationToken): self
    {
        $this->validationToken = $validationToken;
        return $this;
    }

    /**
     * Generate a new random token for email validation.
     *
     * Example: sets validationToken to a 32-character hex string.
     */
    public function generateValidationToken(): self
    {
        $this->validationToken = bin2hex(random_bytes(16));
        return $this;
    }

    public function isEmailValidated(): bool
    {
        return $this->email_validated;
    }

    public function setEmailValidated(bool $emailValidated): self
    {
        $this->email_validated = $emailValidated;
        return $this;
    }

    public function getRegisterWorkflows(): Collection
    {
        return $this->registerWorkflows;
    }

    public function addRegisterWorkflow(RegisterWorkflow $workflow): self
    {
        if (!$this->registerWorkflows->contains($workflow)) {
            $this->registerWorkflows->add($workflow);
        }
        return $this;
    }

}