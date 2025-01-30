<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use App\Enum\TimeSlot;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[UniqueEntity(
    fields: ['resource', 'startTime', 'timeSlot'],
    message: 'This resource is already reserved for this time slot.'
)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotBlank(message: "Start time is required.")]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotBlank(message: "End time is required.")]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\ManyToOne(targetEntity: SharedResource::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SharedResource $resource = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reservedBy = null;

    #[ORM\Column(type: 'string', enumType: TimeSlot::class)]
    #[Assert\NotNull(message: 'Please select a time slot.')]
    private ?TimeSlot $timeSlot = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getResource(): ?SharedResource
    {
        return $this->resource;
    }

    public function setResource(?SharedResource $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getReservedBy(): ?User
    {
        return $this->reservedBy;
    }

    public function setReservedBy(?User $reservedBy): self
    {
        $this->reservedBy = $reservedBy;

        return $this;
    }

    public function getTimeSlot(): ?TimeSlot
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(TimeSlot $timeSlot): self
    {
        $this->timeSlot = $timeSlot;
        
        // Automatically set start and end times based on the time slot
        $date = $this->startTime ? $this->startTime : new \DateTime();
        $this->startTime = new \DateTime($date->format('Y-m-d') . ' ' . $timeSlot->getStartTime());
        $this->endTime = new \DateTime($date->format('Y-m-d') . ' ' . $timeSlot->getEndTime());

        return $this;
    }
}
