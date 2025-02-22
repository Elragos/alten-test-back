<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class WishlistController extends AbstractController
{
    /** GET methods */

    #[Route(
        '/wishlist',
        name: 'wishlist_index',
        methods: ['GET']
    )]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->json($this->getUser()->getWishList(), 200, [], [
            'groups' => ['product.index', 'user.index']
        ]);
    }

    /** POST methods */

    #[Route(
        '/wishlist/{id}',
        name: 'wishlist_add',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['POST']
    )]
    public function addProduct(
        Request $request,
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
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
        '/wishlist/{id}',
        name: 'wishlist_add',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['DELETE']
    )]
    public function removeProduct(
        Request $request,
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
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