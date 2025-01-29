<?php

namespace App\Entity;

use App\Repository\SharedResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedResourceRepository::class)]
class SharedResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brandModel = null; // "Marque et modèle"

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $commissioningDate = null; // "Date de mise en service"

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "boolean")]
    private bool $isArchived = false; // New property for archiving

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: Reservation::class, cascade: ['persist', 'remove'])]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getBrandModel(): ?string
    {
        return $this->brandModel;
    }

    public function setBrandModel(?string $brandModel): self
    {
        $this->brandModel = $brandModel;
        return $this;
    }

    public function getCommissioningDate(): ?\DateTimeInterface
    {
        return $this->commissioningDate;
    }

    public function setCommissioningDate(?\DateTimeInterface $commissioningDate): self
    {
        $this->commissioningDate = $commissioningDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setResource($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getResource() === $this) {
                $reservation->setResource(null);
            }
        }
        return $this;
    }
}
