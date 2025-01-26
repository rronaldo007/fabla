<?php

namespace App\Entity;

use App\Repository\SubjectStudyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectStudyRepository::class)]
class SubjectStudy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $videoPresantation = null;

    #[ORM\OneToOne(mappedBy: 'subject', cascade: ['persist', 'remove'])]
    private ?Submission $submission = null;

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

    public function getVideoPresantation(): ?string
    {
        return $this->videoPresantation;
    }

    public function setVideoPresantation(string $videoPresantation): static
    {
        $this->videoPresantation = $videoPresantation;

        return $this;
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): static
    {
        // unset the owning side of the relation if necessary
        if ($submission === null && $this->submission !== null) {
            $this->submission->setSubject(null);
        }

        // set the owning side of the relation if necessary
        if ($submission !== null && $submission->getSubject() !== $this) {
            $submission->setSubject($this);
        }

        $this->submission = $submission;

        return $this;
    }
}
