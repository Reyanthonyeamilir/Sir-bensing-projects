<?php

namespace App\Entity;

use App\Repository\PetSaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetSaleRepository::class)]
class PetSale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 100)]
    private ?string $dogbreed = null;

    #[ORM\Column]
    private ?int $dogage = null;

    #[ORM\Column]
    private ?\DateTime $datepurchased = null;

    #[ORM\Column]
    private ?\DateTime $datetosale = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $discription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDogbreed(): ?string
    {
        return $this->dogbreed;
    }

    public function setDogbreed(string $dogbreed): static
    {
        $this->dogbreed = $dogbreed;

        return $this;
    }

    public function getDogage(): ?int
    {
        return $this->dogage;
    }

    public function setDogage(int $dogage): static
    {
        $this->dogage = $dogage;

        return $this;
    }

    public function getDatepurchased(): ?\DateTime
    {
        return $this->datepurchased;
    }

    public function setDatepurchased(\DateTime $datepurchased): static
    {
        $this->datepurchased = $datepurchased;

        return $this;
    }

    public function getDatetosale(): ?\DateTime
    {
        return $this->datetosale;
    }

    public function setDatetosale(\DateTime $datetosale): static
    {
        $this->datetosale = $datetosale;

        return $this;
    }

    public function getDiscription(): ?string
    {
        return $this->discription;
    }

    public function setDiscription(string $discription): static
    {
        $this->discription = $discription;

        return $this;
    }
}
