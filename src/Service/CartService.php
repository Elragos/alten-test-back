<?php

namespace App\Service;

use App\Entity\Product;
use App\Service\ProductService;
use App\Utils\Cart;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartService
{

    private ProductService $productService;

    private TranslatorInterface $translator;

    public function __construct(
        ProductService $productService,
        TranslatorInterface $translator
    ) {
        $this->productService = $productService;
        $this->translator = $translator;
    }

    public function add(Cart $cart, string $productCode, int $quantity): void
    {
        // Get correspongind product
        $product = $this->getProduct($productCode);
        // Add product to cart with desired quantity
        $cartItem = $cart->addItem($product, $quantity);

        $errors = [];

        // Limit quantity to product stock
        $productStock = $product->getQuantity();
        if ($cartItem->getQuantity() > $productStock) {
            $cartItem->setQuantity($productStock);
            $errors[] = $this->translator->trans("cart.item.not_enough_stock", [
                "code" => $productCode,
                "stock" => $productStock
            ], "errors");
        }
        // Remove item if quantity negative or null
        if ($cartItem->getQuantity()<= 0) {
            $cart->removeItem($product);
            $errors[] = $this->translator->trans("cart.item.quantity_zero", [
                "code" => $productCode
            ], "errors");
        }
        // Set cart errors
        $cart->setErrors($errors);
    }

    public function remove(Cart $cart, string $productCode): void
    {
        $product = $this->getProduct($productCode);
        $cart->removeItem($product);
    }

    private function getProduct(string $productCode): Product
    {
        // Get correspongind product
        $product = $this->productService->getByCode($productCode);
        // If not found
        if (empty($product)) 
        {
            // Throw error
            throw new InvalidArgumentException("productCode");
        }

        return $product;
    }
}