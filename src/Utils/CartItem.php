<?php

namespace App\Utils;

use App\Entity\Product;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class representing a cart item.
 */
class CartItem
{
    /**
     * @var Product Item product.
     */
    #[Groups(['product.index'])]
    private Product $product;

    /**
     * @var int Item quantity.
     */
    #[Groups(['product.index'])]
    private int $quantity;

    /**
     * Create new cart item.
     * @param Product $product Item product.
     * @param int $quantity Item quantity.
     */
    public function __construct(Product $product, int $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    /**
     * Get Item product.
     * @return Product Item product.
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * Get Item product.
     * @return int Item product.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set Item product.
     * @param int $quantity Item product.
     * @return CartItem
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
}