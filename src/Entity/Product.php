<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product.index'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Code cannot be empty.')]
    #[Groups(['product.index', 'product.create'])]
    private string $code;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create'])]
    private string $name;

    #[ORM\Column(length: 1000)]
    #[Groups(['product.detail', 'product.create'])]
    private string $description;

    #[ORM\Column(length: 255)]
    #[Groups(['product.detail', 'product.create'])]
    private string $image;

    #[ORM\Column(length: 255)]
    #[Groups(['product.index', 'product.create'])]
    private string $category;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create'])]
    private float $price;

    #[ORM\Column]
    #[Assert\Positive()]
    #[Groups(['product.index', 'product.create'])]
    private int $quantity;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create'])]
    private string $internalReference;

    #[ORM\Column]
    #[Groups(['product.index', 'product.create'])]
    private int $shellId;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'])]
    #[Groups(['product.index', 'product.create'])]
    private string $inventoryStatus;

    #[ORM\Column]
    #[Groups(['product.index', 'product.create'])]
    private float $rating;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); 
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getInternalReference(): string
    {
        return $this->internalReference;
    }

    public function setInternalReference(string $internalReference): static
    {
        $this->internalReference = $internalReference;

        return $this;
    }

    public function getShellId(): int
    {
        return $this->shellId;
    }

    public function setShellId(int $shellId): static
    {
        $this->shellId = $shellId;

        return $this;
    }

    public function getInventoryStatus(): string
    {
        return $this->inventoryStatus;
    }

    public function setInventoryStatus(string $inventoryStatus): static
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
