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

#[Assert\Cascade]
class ProductController extends AbstractController
{
    /** GET methods */

    #[Route('/product', name: 'product_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->json($products, 200, [], [
            'groups' => ['product.index']
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
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

    #[Route('/product', name: 'product_create', methods: ['POST'])]
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
    )
    {
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
    
}