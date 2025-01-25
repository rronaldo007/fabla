<?php

namespace App\Entity;

use App\Repository\SpecializationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: SpecializationRepository::class)]
#[Broadcast]
class Specialization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(mappedBy: 'specialization', cascade: ['persist', 'remove'])]
    private ?CandidateProfile $candidateProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($candidateProfile === null && $this->candidateProfile !== null) {
            $this->candidateProfile->setSpecialization(null);
        }

        // set the owning side of the relation if necessary
        if ($candidateProfile !== null && $candidateProfile->getSpecialization() !== $this) {
            $candidateProfile->setSpecialization($this);
        }

        $this->candidateProfile = $candidateProfile;

        return $this;
    }
}
