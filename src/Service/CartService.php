<?php

namespace App\Service;

use App\Entity\Product;
use App\Service\ProductService;
use App\Utils\Cart;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class allowing to interact with a user cart.
 */
class CartService
{

    /**
     * @var ProductService Used product service.
     */
    private ProductService $productService;

    /**
     * @var TranslatorInterface Used translator interface.
     */
    private TranslatorInterface $translator;

    /**
     * Initialize service.
     * @param ProductService $productService Used product service.
     * @param TranslatorInterface $translator Used translator interface.
     */
    public function __construct(
        ProductService $productService,
        TranslatorInterface $translator
    ) {
        $this->productService = $productService;
        $this->translator = $translator;
    }

    /**
     * Add desired product with specific quantity to cart.
     * Quantity added is limited to product stock.
     * @param Cart $cart User cart.
     * @param string $productCode Desired product code.
     * @param int $quantity Specified quantity.
     * @return void
     */
    public function add(Cart $cart, string $productCode, int $quantity): void
    {
        // Get correspongind product
        $product = $this->getProduct($productCode);
        // Add product to cart with desired quantity
        $cartItem = $cart->addItem($product, $quantity);

        // Generated cart errors.
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

    /**
     * Remove product from cart.
     * @param Cart $cart User cart.
     * @param string $productCode Desired product code.
     * @return void
     */
    public function remove(Cart $cart, string $productCode): void
    {
        $product = $this->getProduct($productCode);
        $cart->removeItem($product);
    }

    /**
     * Get product from product code.
     * @param string $productCode Product code.
     * @throws InvalidArgumentException If product not found.
     * @return Product Found product.
     */
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
        // Else return found product
        return $product;
    }
}