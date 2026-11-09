<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\Table(name: 'vehicles')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(
        type: 'smallint',
        columnDefinition: 'TINYINT UNSIGNED NOT NULL'
    )]
    private ?int $seats = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(name: 'vehicle_class', length: 60)]
    private ?string $vehicleClass = null;

    #[ORM\Column(name: 'image_path', length: 255)]
    private ?string $imagePath = null;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getVehicleClass(): ?string
    {
        return $this->vehicleClass;
    }

    public function setVehicleClass(string $vehicleClass): static
    {
        $this->vehicleClass = $vehicleClass;
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
}
