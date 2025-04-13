<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Wishlist;
use App\Service\WishlistService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing wishlist.
 */
class WishlistController extends AbstractController
{

    /**
     * @var WishlistService Used wishlist service.
     */
    private WishlistService $wishlistService;

    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    /**
     * Generate controller.
     *
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(
        WishlistService $wishlistService,
        TranslatorInterface $translator
    ) {
        $this->wishlistService = $wishlistService;
        $this->translator = $translator;
    }

    /** GET methods */

    /**
     * List current user's wishlist products.
     *
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/wishlist',
        name: 'wishlist_index',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function index(): Response
    {
        // Get user wishlist
        $wishlist = $this->wishlistService->get($this->getUser());

        // Return current user wishlit products
        return $this->json(
            $this->getWishlistProducts($wishlist),
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }

    /** POST methods */

    /**
     * Add product to current user's wishlist.
     *
     * @param string $code Product code to add.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/wishlist/{code}',
        name: 'wishlist_add',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function addProduct(string $code): Response
    {
        // Add product to wishlist
        try {
            $wishlist = $this->wishlistService->addProduct($this->getUser(), $code);
        }
        // If product not found
        catch (InvalidArgumentException) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }

        // Send updated wishlist data
        return $this->json(
            $this->getWishlistProducts($wishlist),
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }

    /** DELETE methods */

    /**
     * Remove product from current user's wishlist.
     *
     * @param string $code Product code to remove.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/wishlist/{code}',
        name: 'wishlist_remove',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function removeProduct(string $code): Response
    {
        // Remove product from wishlist
        try {
            $wishlist = $this->wishlistService->removeProduct($this->getUser(), $code);
        }
        // If product not found
        catch (InvalidArgumentException) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }

        // Send updated wishlist data
        return $this->json(
            $this->getWishlistProducts($wishlist),
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }

    /**
     * Get wishlist products ready to be sent in JSON.
     * @param Wishlist||null $wishlist Selected wishlist.
     * @return Product[] Wishlist products, or empty array if wishlist is null.
     */
    private function getWishlistProducts(?Wishlist $wishlist): array
    {
        if ($wishlist == null) {
            return [];
        }
        return $wishlist->getProducts()->toArray();
    }
}