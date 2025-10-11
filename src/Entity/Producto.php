<?php

namespace App\Entity;

use App\Repository\ProductoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Petname = null;

    #[ORM\Column]
    private ?float $Age = null;

    #[ORM\Column(length: 255)]
    private ?string $Product_name = null;

    #[ORM\Column]
    private ?float $Price = null;

    #[ORM\Column]
    private ?int $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPetname(): ?string
    {
        return $this->Petname;
    }

    public function setPetname(string $Petname): static
    {
        $this->Petname = $Petname;

        return $this;
    }

    public function getAge(): ?float
    {
        return $this->Age;
    }

    public function setAge(float $Age): static
    {
        $this->Age = $Age;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->Product_name;
    }

    public function setProductName(string $Product_name): static
    {
        $this->Product_name = $Product_name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->Price;
    }

    public function setPrice(float $Price): static
    {
        $this->Price = $Price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
