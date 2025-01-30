<?php

namespace App\Entity;

use App\Repository\SubmissionWorkflowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionWorkflowRepository::class)]
class SubmissionWorkflow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $state = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $transltionedAt = null;

    #[ORM\ManyToOne(inversedBy: 'submissionWorkflows')]
    private ?Submission $submission = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getTransltionedAt(): ?\DateTimeInterface
    {
        return $this->transltionedAt;
    }

    public function setTransltionedAt(?\DateTimeInterface $transltionedAt): static
    {
        $this->transltionedAt = $transltionedAt;

        return $this;
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): static
    {
        $this->submission = $submission;
        return $this;
    }
}
