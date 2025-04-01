<?php
// School.php
namespace App\Entity;

use App\Repository\SchoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchoolRepository::class)]
class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $location = null;

    #[ORM\OneToMany(mappedBy: 'currentSchool', targetEntity: CandidateProfile::class)]
    private Collection $candidateProfiles;

    public function __construct()
    {
        $this->candidateProfiles = new ArrayCollection();
    }

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getCandidateProfiles(): Collection
    {
        return $this->candidateProfiles;
    }

    public function addCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if (!$this->candidateProfiles->contains($candidateProfile)) {
            $this->candidateProfiles->add($candidateProfile);
            $candidateProfile->setCurrentSchool($this);
        }

        return $this;
    }

    public function removeCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if ($this->candidateProfiles->removeElement($candidateProfile)) {
            // set the owning side to null (unless already changed)
            if ($candidateProfile->getCurrentSchool() === $this) {
                $candidateProfile->setCurrentSchool(null);
            }
        }

        return $this;
    }
}