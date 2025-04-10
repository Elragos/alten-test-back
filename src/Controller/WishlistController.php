<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing user.
 */
class WishlistController extends AbstractController
{
    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    /**
     * Generate controller.
     *
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(TranslatorInterface $translator)
    {
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
        $wishlist = $this->getUser()->getWishlist();

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
     * @param EntityManagerInterface $em Used entity manager.
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
    public function addProduct(
        string $code,
        EntityManagerInterface $em
    ): Response {
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->findOneBy([
            'code' => $code
        ]);
        // If no match
        if (!$product) {
            // Send 404 error
            throw $this->createNotFoundException(
                $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            );
        }
        // Get user wishlist
        $wishlist = $this->getUser()->getWishList();
        // If not created
        if (!$wishlist) {
            // Create it
            $wishlist = new Wishlist();
            $wishlist->setUser($this->getUser());
        }
        // Add product to wishlist (if already in wishlist, nothing happens)
        $wishlist->addProduct($product);
        // Save wishlist
        $em->persist($wishlist);
        $em->flush();

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
     * @param EntityManagerInterface $em
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
    public function removeProduct(
        string $code,
        EntityManagerInterface $em
    ): Response {
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->findOneBy([
            'code'=> $code
        ]);
        // If no match
        if (!$product) {
            // Send 404 error
            throw $this->createNotFoundException(
                $this->translator->trans("product.code_not_found", ["code" => $code], "errors")
            );
        }
        // Get user wishlist
        $wishlist = $this->getUser()->getWishList();
        // If exists
        if ($wishlist) {
            // Remove product from wishlist (if not found, nothing happens)
            $wishlist->removeProduct($product);
            // Save wishlist
            $em->persist($wishlist);
            $em->flush();
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