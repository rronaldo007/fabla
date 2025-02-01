<?php

namespace App\Entity;

use App\Repository\EditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditionRepository::class)]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', unique: true)]
    private int $year;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startPublication;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startApplication;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $endApplication;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $announcementDate;

    #[ORM\Column(type: 'boolean')]
    private bool $isCurrent = false;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\ManyToMany(targetEntity: Submission::class, mappedBy: 'editions')]
    private Collection $submissions;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getStartPublication(): \DateTimeInterface
    {
        return $this->startPublication;
    }

    public function setStartPublication(\DateTimeInterface $startPublication): self
    {
        $this->startPublication = $startPublication;

        return $this;
    }

    public function getStartApplication(): \DateTimeInterface
    {
        return $this->startApplication;
    }

    public function setStartApplication(\DateTimeInterface $startApplication): self
    {
        $this->startApplication = $startApplication;

        return $this;
    }

    public function getEndApplication(): \DateTimeInterface
    {
        return $this->endApplication;
    }

    public function setEndApplication(\DateTimeInterface $endApplication): self
    {
        $this->endApplication = $endApplication;

        return $this;
    }

    public function getAnnouncementDate(): \DateTimeInterface
    {
        return $this->announcementDate;
    }

    public function setAnnouncementDate(\DateTimeInterface $announcementDate): self
    {
        $this->announcementDate = $announcementDate;

        return $this;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function setIsCurrent(bool $isCurrent): self
    {
        $this->isCurrent = $isCurrent;

        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->addEdition($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            $submission->removeEdition($this);
        }

        return $this;
    }
}