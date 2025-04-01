<?php
// Nationality.php
namespace App\Entity;

use App\Repository\NationalityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'nationality', targetEntity: CandidateProfile::class)]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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
            $candidateProfile->setNationality($this);
        }

        return $this;
    }

    public function removeCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if ($this->candidateProfiles->removeElement($candidateProfile)) {
            // set the owning side to null (unless already changed)
            if ($candidateProfile->getNationality() === $this) {
                $candidateProfile->setNationality(null);
            }
        }

        return $this;
    }
}