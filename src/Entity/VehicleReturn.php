<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vehicle_returns')]
#[ORM\UniqueConstraint(name: 'uniq_vehicle_return_reservation', columns: ['reservation_id'])]
class VehicleReturn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 20)]
    private string $customerCondition = 'none';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerNotes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerPhoto = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $inspectedCondition = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $inspectionNotes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inspectionPhoto = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'inspected_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $inspectedBy = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $requestedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $confirmedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(Reservation $reservation): static { $this->reservation = $reservation; return $this; }
    public function getCustomerCondition(): string { return $this->customerCondition; }
    public function setCustomerCondition(string $condition): static { $this->customerCondition = $condition; return $this; }
    public function getCustomerNotes(): ?string { return $this->customerNotes; }
    public function setCustomerNotes(?string $notes): static { $this->customerNotes = $notes; return $this; }
    public function getCustomerPhoto(): ?string { return $this->customerPhoto; }
    public function setCustomerPhoto(?string $photo): static { $this->customerPhoto = $photo; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getInspectedCondition(): ?string { return $this->inspectedCondition; }
    public function setInspectedCondition(?string $condition): static { $this->inspectedCondition = $condition; return $this; }
    public function getInspectionNotes(): ?string { return $this->inspectionNotes; }
    public function setInspectionNotes(?string $notes): static { $this->inspectionNotes = $notes; return $this; }
    public function getInspectionPhoto(): ?string { return $this->inspectionPhoto; }
    public function setInspectionPhoto(?string $photo): static { $this->inspectionPhoto = $photo; return $this; }
    public function getInspectedBy(): ?User { return $this->inspectedBy; }
    public function setInspectedBy(?User $user): static { $this->inspectedBy = $user; return $this; }
    public function getRequestedAt(): ?\DateTime { return $this->requestedAt; }
    public function setRequestedAt(?\DateTime $date): static { $this->requestedAt = $date; return $this; }
    public function getConfirmedAt(): ?\DateTime { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTime $date): static { $this->confirmedAt = $date; return $this; }
}
