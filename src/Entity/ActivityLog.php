<?php
// src/Entity/ActivityLog.php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\HasLifecycleCallbacks]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $userId = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;

    #[ORM\Column(length: 20)]
    private ?string $action = null; // CREATE, UPDATE, DELETE, VIEW

    #[ORM\Column(type: 'text')]
    private ?string $targetData = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $entityType = null; // NEW FIELD: e.g., 'User', 'Petproducts', 'Order'

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ipAddress = null; // NEW FIELD

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        // Set Manila time
        $this->createdAt = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(?User $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(string $targetData): static
    {
        $this->targetData = $targetData;
        return $this;
    }

    // NEW GETTER/SETTER FOR entityType
    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    // NEW GETTER/SETTER FOR ipAddress
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}