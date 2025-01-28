<?php

namespace App\Entity;

use App\Repository\NationalityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NationalityRepository::class)]
class Nationality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code = null;

    #[ORM\OneToOne(mappedBy: 'nationality', cascade: ['persist', 'remove'])]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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
            $this->candidateProfile->setNationality(null);
        }

        // set the owning side of the relation if necessary
        if ($candidateProfile !== null && $candidateProfile->getNationality() !== $this) {
            $candidateProfile->setNationality($this);
        }

        $this->candidateProfile = $candidateProfile;

        return $this;
    }
}
