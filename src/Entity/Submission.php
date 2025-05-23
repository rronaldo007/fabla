<?php

namespace App\Entity;

use App\Repository\SubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[Broadcast]
class Submission 
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $identifier = null;

    #[ORM\Column(length: 100)]
    private ?string $currentState = null;

    #[ORM\Column]
    private ?bool $isSubmissionAccepted = null;

    #[ORM\Column]
    private ?bool $isCandidateAccepted = null;

    /**
     * @var Collection<int, SubmissionWorkflow>
     */
    #[ORM\OneToMany(targetEntity: SubmissionWorkflow::class, mappedBy: 'submission')]
    private Collection $submissionWorkflows;

    #[ORM\ManyToOne(targetEntity: CandidateProfile::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\OneToOne(inversedBy: 'submission', cascade: ['persist', 'remove'])]
    private ?SubjectStudy $subject = null;

    /**
     * @var Collection<int, Edition>
     */
    #[ORM\ManyToMany(targetEntity: Edition::class, inversedBy: 'submissions')]
    #[ORM\JoinTable(name: 'submission_edition')]
    private Collection $editions;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(mappedBy: 'submission', targetEntity: Evaluation::class, cascade: ['persist', 'remove'])]
    private Collection $evaluations;



    public function __construct()
    {
        $this->submissionWorkflows = new ArrayCollection();
        $this->editions = new ArrayCollection();
        $this->evaluations = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getCurrentState(): ?string
    {
        return $this->currentState;
    }

    public function setCurrentState(string $currentState): static
    {
        $this->currentState = $currentState;

        return $this;
    }

    public function isSubmissionAccepted(): ?bool
    {
        return $this->isSubmissionAccepted;
    }

    public function setIsSubmissionAccepted(bool $isSubmissionAccepted): static
    {
        $this->isSubmissionAccepted = $isSubmissionAccepted;

        return $this;
    }

    public function isCandidateAccepted(): ?bool
    {
        return $this->isCandidateAccepted;
    }

    public function setIsCandidateAccepted(bool $isCandidateAccepted): static
    {
        $this->isCandidateAccepted = $isCandidateAccepted;

        return $this;
    }

    /**
     * @return Collection<int, SubmissionWorkflow>
     */
    public function getSubmissionWorkflows(): Collection
    {
        return $this->submissionWorkflows;
    }

    public function addSubmissionWorkflow(SubmissionWorkflow $submissionWorkflow): static
    {
        if (!$this->submissionWorkflows->contains($submissionWorkflow)) {
            $this->submissionWorkflows->add($submissionWorkflow);
            $submissionWorkflow->setSubmission($this);
        }

        return $this;
    }

    public function removeSubmissionWorkflow(SubmissionWorkflow $submissionWorkflow): static
    {
        if ($this->submissionWorkflows->removeElement($submissionWorkflow)) {
            if ($submissionWorkflow->getSubmission() === $this) {
                $submissionWorkflow->setSubmission(null);
            }
        }

        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        $this->candidateProfile = $candidateProfile;

        return $this;
    }

    public function getSubject(): ?SubjectStudy
    {
        return $this->subject;
    }

    public function setSubject(?SubjectStudy $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Collection<int, Edition>
     */
    public function getEditions(): Collection
    {
        return $this->editions;
    }

    public function addEdition(Edition $edition): static
    {
        if (!$this->editions->contains($edition)) {
            $this->editions->add($edition);
            $edition->addSubmission($this);
        }

        return $this;
    }

    public function removeEdition(Edition $edition): static
    {
        if ($this->editions->removeElement($edition)) {
            $edition->removeSubmission($this);
        }

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
            $evaluation->setSubmission($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            if ($evaluation->getSubmission() === $this) {
                $evaluation->setSubmission(null);
            }
        }

        return $this;
    }
}