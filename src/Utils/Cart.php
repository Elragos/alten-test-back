<?php

namespace App\Utils;

use App\Entity\Product;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class representing a user cart.
 */
class Cart
{
    /**
     * @var CartItem[] Cart item list.
     */
    #[Groups(['product.index'])]
    private array $items;

    /**
     * @var string[] Cart error list.
     */
    private array $errors;

    /**
     * Create new cart.
     * @param CartItem[] $items Cart item list.
     */
    public function __construct(array $items)
    {
        $this->items = $items;
        $this->errors = [];
    }

    /**
     * Get cart items.
     * @return CartItem[] Cart items.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get cart errors.
     * @return string[] Cart errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set cart errors.
     * @param string[] $error Error list.
     * @return void
     */
    public function setErrors(array $error): void
    {
        $this->errors = $error;
    }

    /**
     * Add product to cart.
     * @param Product $product Desired product.
     * @param int $quantity Desired quantity.
     * @return CartItem Resulting cart item.
     */
    public function addItem(Product $product, int $quantity): CartItem
    {
        // For each items in cart
        foreach ($this->items as $item) {
            // If product in cart
            if ($item->getProduct()->getId() == $product->getId()) {
                // Add quantity to existing item
                $item->setQuantity($item->getQuantity() + $quantity);
                // Return item
                return $item;
            }
        }
        // Else create item
        $item = new CartItem($product, $quantity);
        // Add it to item list
        $this->items[] = $item;
        // Return created item
        return $item;
    }

    /**
     * Remove product from cart.
     * @param Product $product
     * @return void
     */
    public function removeItem(Product $product): void
    {
        // For each items in cart
        foreach ($this->items as $index => $item) {
            // If product in cart
            if ($item->getProduct()->getId() == $product->getId()) {
                // Remove item from list
                unset($this->items[$index]);
            }
        }
    }
}