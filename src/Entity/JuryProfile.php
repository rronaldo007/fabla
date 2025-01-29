<?php

namespace App\Entity;

use App\Repository\JuryProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: JuryProfileRepository::class)]
#[Broadcast]
class JuryProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $miniCv = null;

    #[ORM\OneToOne(inversedBy: 'juryProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserProfile $userProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;
        return $this;
    }

    public function getMiniCv(): ?string
    {
        return $this->miniCv;
    }

    public function setMiniCv(?string $miniCv): static
    {
        $this->miniCv = $miniCv;
        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): static
    {
        $this->userProfile = $userProfile;

        // Ensure bidirectional setting
        if ($userProfile !== null && $userProfile->getJuryProfile() !== $this) {
            $userProfile->setJuryProfile($this);
        }

        return $this;
    }
}
