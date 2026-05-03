<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\ConfigurationRepository')]
#[ORM\Table(name: 'configuration')]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Car $car = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $color = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $designPackage = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $interior = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?int $totalPrice = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'Pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getDesignPackage(): ?string
    {
        return $this->designPackage;
    }

    public function setDesignPackage(string $designPackage): static
    {
        $this->designPackage = $designPackage;
        return $this;
    }

    public function getInterior(): ?string
    {
        return $this->interior;
    }

    public function setInterior(string $interior): static
    {
        $this->interior = $interior;
        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // Helper method to get formatted total price
    public function getFormattedTotalPrice(): string
    {
        return '$' . number_format($this->totalPrice, 0, ',', '.');
    }
}