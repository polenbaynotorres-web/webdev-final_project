<?php

namespace App\Entity;

use App\Repository\SubscriberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
#[ORM\Table(name: 'subscribers')]
class Subscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscribers')]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user = null;

    #[ORM\Column(length: 190, unique: true)]
    private ?string $email = null;

    #[ORM\Column(
        name: 'subscribed_at',
        type: Types::DATETIME_MUTABLE,
        options: ['default' => 'CURRENT_TIMESTAMP']
    )]
    private ?\DateTime $subscribedAt = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTime $subscribedAt): static
    {
        $this->subscribedAt = $subscribedAt;

        return $this;
    }
}
