<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;

/**
 * Service handling all product manipulations.
 */
class ProductService
{
    /**
     * @var ProductRepository Used product repository.
     */
    private ProductRepository $productRepository;

    /**
     * Initialize service
     * @param ProductRepository $productRepository Used product repository.
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get all available products.
     * @return Product[]
     */
    public function getAll(): array
    {
        return $this->productRepository->findAll();
    }

    /**
     * Get product by code.
     * @param string $code Desired product code.
     * @return Product|null Found product or null if code not used by any product.
     */
    public function getByCode(string $code): ?Product
    {
        return $this->productRepository->findOneBy(["code" => $code]);
    }

    /**
     * Create new product.
     * @param Product $product Product data.
     * @return void
     * @throws InvalidArgumentException If product code is already used.
     */
    public function create(Product $product) : void
    {
        // Check that desired code is not used by another product
        $colliding = $this->getByCode($product->getCode());

        // If match
        if ($colliding != null) {
            throw new InvalidArgumentException();
        }

        // Set auto generated product info
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        // Save product in DB
        $this->productRepository->save($product, true);
    }

    /**
     * Update product.
     * @param string $code Product code to update
     * @param Product $product Product data.
     * @return Product Created product
     * @throws InvalidArgumentException If product code is already used.
     */
    public function update(string $code, Product $newData): Product
    {
        // Fetch wanted product in DB
        $product = $this->getByCode($code);
        // If no match
        if ($product == null) {
            throw new EntityNotFoundException();
        }
        // Check that desired code is not used by another product
        $colliding = $this->getByCode($newData->getCode());

        // If match and not same product
        if ($colliding != null && $colliding->getId() != $product->getId()) {
            throw new InvalidArgumentException();
        }

        // Merge product data
        $product->mergeNewData($newData);
        $this->productRepository->save($product, true);
        return $product;
    }

    /**
     * Delete product from DB.
     * @param string $code Desired product code.
     * @throws EntityNotFoundException If product not found.
     * @return Product Deleted product.
     */
    public function delete(string $code): Product
    {
        // Get product by code
        $product = $this->getByCode($code);
        // If not found
        if ($product == null) {
            // Throw error
            throw new EntityNotFoundException();
        }
        // Remove product
        $this->productRepository->remove($product, true);
        return $product;
    }
}
