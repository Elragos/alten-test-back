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

class WishlistController extends AbstractController
{
    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /** GET methods */

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
        Request $request,
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }
        $wishlist = $this->getUser()->getWishList();
        if (!$wishlist)
        {
            $wishlist = new Wishlist();
            $wishlist->setUser($this->getUser());
        }
        $wishlist->addProduct($product);

        $em->persist($wishlist);
        $em->flush();

        return $this->json($wishlist, 201, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }

    /** DELETE methods */

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
        Request $request,
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }
        $wishlist = $this->getUser()->getWishList();
        if (!$wishlist)
        {
            $wishlist = new Wishlist();
            $wishlist->setUser($this->getUser());
        }
        $wishlist->removeProduct($product);

        $em->persist($wishlist);
        $em->flush();

        return $this->json($wishlist, 201, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }
}