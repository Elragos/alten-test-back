<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class ProductController extends AbstractController
{
    /** GET methods */

    #[Route(
        '/product',
        name: 'product_index',
        methods: ['GET']
    )]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->json($products, 200, [], [
            'groups' => ['product.index']
        ]);
    }

    #[Route(
        '/product/{id}',
        name: 'product_show',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
    
    /** POST methods */

    #[Route(
        '/product',
        name: 'product_create',
        methods: ['POST']
    )]
    public function create(
        Request $request,
        #[MapRequestPayload(
            acceptFormat: "json",
            serializationContext: [
                'groups' => ['product.create']
            ]
        )]
        Product $product,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** PATCH methods */

    #[Route(
        '/product/{id}',
        name: 'product_update',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['PATCH']
    )]
    public function update(
        Request $request,
        #[MapRequestPayload(
            acceptFormat: "json",
            serializationContext: [
                'groups' => ['product.update']
            ]
        )]
        Product $newData,
        int $id,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }

        $product->mergeNewData($newData);
        $em->persist($product);
        $em->flush();

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** DELETE methods */

    #[Route(
        '/product/{id}',
        name: 'product_update',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['DELETE']
    )]
    public function delete(
        Request $request,
        int $id,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }

        $em->remove($product);
        $em->flush();

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
}