<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(
        name: 'vehicle_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?Vehicle $vehicle = null;

    // The database uses a composite foreign key:
    // (vehicle_id, color_id) -> vehicle_colors(vehicle_id, id).
    // Keep color_id scalar to avoid creating an incorrect separate FK.
    #[ORM\Column(
        name: 'color_id',
        type: Types::INTEGER,
        options: ['unsigned' => true]
    )]
    private ?int $colorId = null;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(
        name: 'location_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?Location $location = null;

    #[ORM\Column(length: 30)]
    private ?string $phone = null;

    #[ORM\Column(name: 'pickup_date', type: Types::DATE_MUTABLE)]
    private ?\DateTime $pickupDate = null;

    #[ORM\Column(name: 'pickup_time', type: Types::TIME_MUTABLE)]
    private ?\DateTime $pickupTime = null;

    #[ORM\Column(name: 'return_date', type: Types::DATE_MUTABLE)]
    private ?\DateTime $returnDate = null;

    #[ORM\Column(name: 'return_time', type: Types::TIME_MUTABLE)]
    private ?\DateTime $returnTime = null;

    #[ORM\Column(name: 'payment_method', length: 30, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(
        type: Types::STRING,
        columnDefinition: "ENUM('confirmed','cancelled') DEFAULT 'confirmed'"
    )]
    private ?string $status = 'confirmed';

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_MUTABLE,
        options: ['default' => 'CURRENT_TIMESTAMP']
    )]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'cancelled_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $cancelledAt = null;

    public function __construct()
    {
        $this->status = 'confirmed';
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): static { $this->vehicle = $vehicle; return $this; }

    public function getColorId(): ?int { return $this->colorId; }
    public function setColorId(int $colorId): static { $this->colorId = $colorId; return $this; }

    public function getLocation(): ?Location { return $this->location; }
    public function setLocation(?Location $location): static { $this->location = $location; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(string $phone): static { $this->phone = $phone; return $this; }

    public function getPickupDate(): ?\DateTime { return $this->pickupDate; }
    public function setPickupDate(\DateTime $pickupDate): static { $this->pickupDate = $pickupDate; return $this; }

    public function getPickupTime(): ?\DateTime { return $this->pickupTime; }
    public function setPickupTime(\DateTime $pickupTime): static { $this->pickupTime = $pickupTime; return $this; }

    public function getReturnDate(): ?\DateTime { return $this->returnDate; }
    public function setReturnDate(\DateTime $returnDate): static { $this->returnDate = $returnDate; return $this; }

    public function getReturnTime(): ?\DateTime { return $this->returnTime; }
    public function setReturnTime(\DateTime $returnTime): static { $this->returnTime = $returnTime; return $this; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(?string $paymentMethod): static { $this->paymentMethod = $paymentMethod; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getCancelledAt(): ?\DateTime { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTime $cancelledAt): static { $this->cancelledAt = $cancelledAt; return $this; }
}
