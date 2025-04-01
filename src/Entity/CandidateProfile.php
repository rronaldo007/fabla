<?php
// CandidateProfile.php
namespace App\Entity;

use App\Repository\CandidateProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: CandidateProfileRepository::class)]
#[Broadcast]
class CandidateProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $programEntryDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $currentYear = null;

    #[ORM\Column(length: 255)]
    private ?string $studentCardPath = null;

    #[ORM\ManyToOne(inversedBy: 'candidateProfiles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?School $currentSchool = null;

    #[ORM\ManyToOne(inversedBy: 'candidateProfiles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Specialization $specialization = null;

    #[ORM\ManyToOne(inversedBy: 'candidateProfiles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Nationality $nationality = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $CV = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'candidat')]
    private Collection $evaluations;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramEntryDate(): ?\DateTimeInterface
    {
        return $this->programEntryDate;
    }

    public function setProgramEntryDate(\DateTimeInterface $programEntryDate): static
    {
        $this->programEntryDate = $programEntryDate;

        return $this;
    }

    public function getCurrentYear(): ?\DateTimeInterface
    {
        return $this->currentYear;
    }

    public function setCurrentYear(\DateTimeInterface $currentYear): static
    {
        $this->currentYear = $currentYear;

        return $this;
    }

    public function getStudentCardPath(): ?string
    {
        return $this->studentCardPath;
    }

    public function setStudentCardPath(string $studentCardPath): static
    {
        $this->studentCardPath = $studentCardPath;

        return $this;
    }

    public function getCurrentSchool(): ?School
    {
        return $this->currentSchool;
    }

    public function setCurrentSchool(?School $currentSchool): static
    {
        $this->currentSchool = $currentSchool;

        return $this;
    }

    public function getSpecialization(): ?Specialization
    {
        return $this->specialization;
    }

    public function setSpecialization(?Specialization $specialization): static
    {
        $this->specialization = $specialization;

        return $this;
    }

    public function getNationality(): ?Nationality
    {
        return $this->nationality;
    }

    public function setNationality(?Nationality $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): static
    {
        $this->userProfile = $userProfile;

        return $this;
    }

    public function getCV(): ?string
    {
        return $this->CV;
    }

    public function setCV(?string $CV): static
    {
        $this->CV = $CV;

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
            $evaluation->setCandidat($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getCandidat() === $this) {
                $evaluation->setCandidat(null);
            }
        }

        return $this;
    }
}