<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity representing an app product.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    /**
     * @var int Product ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    /**
     * @var string Product code.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private string $code;

    /**
     * @var string Product name.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private string $name;

    /**
     * @var string Product description.
     */
    #[ORM\Column(length: 1000)]
    #[Groups(['product.detail', 'product.create', 'product.update'])]
    private string $description;

    /**
     * @var string Product image URL.
     */
    #[ORM\Column(length: 255)]
    #[Groups(['product.detail', 'product.create', 'product.update'])]
    private string $image;

    /**
     * @var string Product category name.
     */
    #[ORM\Column(length: 255)]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private string $category;

    /**
     * @var float Product price.
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private float $price;

    /**
     * @var int Product quantity in stock.
     */
    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private int $quantity;

    /**
     * @var string Product internal reference.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private string $internalReference;

    /**
     * @var int Product shell ID.
     */
    #[ORM\Column]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private int $shellId;

    /**
     * @var string Product inventory status ('INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK')
     */
    #[ORM\Column(length: 255)]
    #[Assert\Choice(['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'])]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private string $inventoryStatus;

    /**
     * @var float Product rating.
     */
    #[ORM\Column]
    #[Groups(['product.index', 'product.create', 'product.update'])]
    private float $rating;

    /**
     * @var \DateTimeImmutable Product creation time.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable Product last updated time.
     */
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Update product data according to newData.
     *
     * @param Product $newData New data to put in product.
     * @return $this
     */
    public function mergeNewData(Product $newData): static
    {
        // For each product attribute
        foreach(get_object_vars($newData) as $key => $value)
        {
            // If set
            if ($value){
                // Replace old value with new
                $this->$key = $value;
            }
        }
        // Change last updated time
        $this->setUpdatedAt(new \DateTimeImmutable());

        // Return updated product
        return $this;
    }
}
