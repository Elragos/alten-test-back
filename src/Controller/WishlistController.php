<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
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
     * List current user's wishlist.
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
        return $this->json($this->getUser()->getWishList(), 200, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }

    /** POST methods */

    /**
     * Add product to current user's wishlist.
     *
     * @param int $id Product ID to add.
     * @param EntityManagerInterface $em Used entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/wishlist/{id}',
        name: 'wishlist_add',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function addProduct(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->find($id);
        // If no match
        if (!$product)
        {
            // Send 404 error
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }
        // Get user wishlist
        $wishlist = $this->getUser()->getWishList();
        // If not created
        if (!$wishlist)
        {
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
        return $this->json($wishlist, 201, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }

    /** DELETE methods */

    /**
     * Remove product from current user's wishlist.
     *
     * @param int $id Product ID to remove.
     * @param EntityManagerInterface $em
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/wishlist/{id}',
        name: 'wishlist_add',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function removeProduct(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->find($id);
        // If no match
        if (!$product) {
            // Send 404 error
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }
        // Get user wishlist
        $wishlist = $this->getUser()->getWishList();
        // If not created
        if (!$wishlist)
        {
            // Create if
            $wishlist = new Wishlist();
            $wishlist->setUser($this->getUser());
        }
        // Remove product from wishlist (if not found, nothing happens)
        $wishlist->removeProduct($product);
        // Save wishlist
        $em->persist($wishlist);
        $em->flush();
        // Send updated wishlist data
        return $this->json($wishlist, 201, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }
}