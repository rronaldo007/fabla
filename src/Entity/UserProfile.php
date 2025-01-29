<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
class UserProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $first_name = null;

    #[ORM\Column(length: 100)]
    private ?string $last_name = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_of_birth = null;

    #[ORM\OneToOne(inversedBy: 'userProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'userProfile', cascade: ['persist', 'remove'])]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isArchived = false; // New property with default value

    #[ORM\OneToOne(mappedBy: 'userProfile', cascade: ['persist', 'remove'])]
    private ?JuryProfile $juryProfile = null;

    public function __construct()
    {
        $this->isArchived = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(?\DateTimeInterface $date_of_birth): static
    {
        $this->date_of_birth = $date_of_birth;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        // Unset the owning side of the relation if necessary
        if ($candidateProfile === null && $this->candidateProfile !== null) {
            $this->candidateProfile->setUserProfile(null);
        }

        // Set the owning side of the relation if necessary
        if ($candidateProfile !== null && $candidateProfile->getUserProfile() !== $this) {
            $candidateProfile->setUserProfile($this);
        }

        $this->candidateProfile = $candidateProfile;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function getJuryProfile(): ?JuryProfile
{
    return $this->juryProfile;
}

public function setJuryProfile(?JuryProfile $juryProfile): static
{
    // Unset the owning side of the relation if necessary
    if ($juryProfile === null && $this->juryProfile !== null) {
        $this->juryProfile->setUserProfile(null);
    }

    // Set the owning side of the relation if necessary
    if ($juryProfile !== null && $juryProfile->getUserProfile() !== $this) {
        $juryProfile->setUserProfile($this);
    }

    $this->juryProfile = $juryProfile;

    return $this;
}
}
