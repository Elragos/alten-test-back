<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Wishlist;
use App\Repository\WishlistRepository;
use InvalidArgumentException;


class WishlistService
{
    /** 
     * @var WishlistRepository Used wishlist Repository.
     */
    private WishlistRepository $wishlistRepository;

    /**
     * @var ProductService Used product sercice.
     */
    private ProductService $productservice;

    /**
     * Initialize service.
     * @param WishlistRepository $wishlistRepository Used wishlist Repository.
     * @param ProductService $productservice Used product service.
     */
    public function __construct(
        WishlistRepository $wishlistRepository,
        ProductService $productservice
    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->productservice = $productservice;
    }

    /**
     * Get user wishlist.
     * @param User $user Selected user.
     * @return null|Wishlist User wishlist, or null if not found.
     */
    public function get(User $user): Wishlist
    {
        // Get wishlist linked to user
        $return = $this->wishlistRepository->findOneBy([
            "user" => $user
        ]);
        // If wishlist is not found
        return $return == null
            // Return empty wishlist linked to user.
            ? new Wishlist()->setUser($user)
            // Else return found wishlist
            : $return;
    }

    /**
     * Add product to user wishlist.
     * @param User $user Desired user.
     * @param string $code Desired product code.
     * @throws InvalidArgumentException If product not found.
     * @return Wishlist Updated wishlist.
     */
    public function addProduct(User $user, string $code): Wishlist
    {
        // Get user wishlist
        $wishlist = $this->get($user);
        // Add desired product
        $wishlist->addProduct($this->getProduct($code));
        // Save wishlist
        $this->wishlistRepository->save($wishlist);
        // Return updated wishlist
        return $wishlist;
    }

    /**
     * Remove product from user wishlist.
     * @param User $user Desired user.
     * @param string $code Desired product code.
     * @throws InvalidArgumentException If product not found.
     * @return Wishlist Updated wishlist.
     */
    public function removeProduct(User $user, string $code): Wishlist
    {
        // Get user wishlist
        $wishlist = $this->get($user);
        // Add desired product
        $wishlist->removeProduct($this->getProduct($code));
        // Save wishlist
        $this->wishlistRepository->save($wishlist);
        // Return updated wishlist
        return $wishlist;
    }

    /**
     * Get product by code.
     * @param string $code Desired product code.
     * @throws InvalidArgumentException If product not found.
     * @return Product Found product.
     */
    private function getProduct(string $code): Product
    {
        $product = $this->productservice->getByCode($code);

        if (empty($product)) {
            throw new InvalidArgumentException("productCode");
        }
        return $product;
    }
}