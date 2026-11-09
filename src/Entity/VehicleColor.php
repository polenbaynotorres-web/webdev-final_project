<?php

namespace App\Entity;

use App\Repository\VehicleColorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleColorRepository::class)]
#[ORM\Table(name: 'vehicle_colors')]
#[ORM\UniqueConstraint(
    name: 'uq_vehicle_color',
    columns: ['vehicle_id', 'color_name']
)]
#[ORM\UniqueConstraint(
    name: 'uq_vehicle_color_pair',
    columns: ['vehicle_id', 'id']
)]
class VehicleColor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(
        name: 'vehicle_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(name: 'color_name', length: 60)]
    private ?string $colorName = null;

    #[ORM\Column(name: 'image_path', length: 255)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'in_stock', type: 'boolean', options: ['default' => 1])]
    private bool $inStock = true;

    #[ORM\Column(
        name: 'updated_at',
        columnDefinition: 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    )]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getColorName(): ?string
    {
        return $this->colorName;
    }

    public function setColorName(string $colorName): static
    {
        $this->colorName = $colorName;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function isInStock(): bool
    {
        return $this->inStock;
    }

    public function setInStock(bool $inStock): static
    {
        $this->inStock = $inStock;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
