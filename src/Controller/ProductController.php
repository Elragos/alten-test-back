<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing app products.
 */
class ProductController extends AbstractController
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
     * List available products.
     *
     * @param EntityManagerInterface $entityManager Used entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/product',
        name: 'product_index',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get all products from DB
        $products = $entityManager->getRepository(Product::class)->findAll();

        // Send product list
        return $this->json($products, 200, [], [
            // Only send useful info for listing
            'groups' => ['product.index']
        ]);
    }

    /**
     * Get all info on a product.
     *
     * @param EntityManagerInterface $entityManager Used entity manager.
     * @param int $id Wanted product ID
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_show',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        // Fetch wanted product with DB
        $product = $entityManager->getRepository(Product::class)->find($id);

        // If no match
        if (!$product) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.id_not_found",
                    ["id" => $id],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
        // Otherwise send detailed information
        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** POST methods */

    /**
     * Add product to database (admin only).
     *  Expected payload :
     * {
     *  "code": "product code",
     *  "name": "product name",
     *  "description": "product description",
     *  "image": "product image URL",
     *  "category": "product category name",
     *  "price": productPrice (decimal),
     *  "quantity": productQuantity (integer),
     *  "internalReference": "product o",
     *  "shellId": 10,
     *  "inventoryStatus": "INSTOCK|LOWSTOCK|OUTOFSTOCK",
     *  "rating": productRating (decimal)
     *  }
     *
     * @param Request $request Client request.
     * @param Product $product Product info
     * @param EntityManagerInterface $em Used entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/product',
        name: 'product_create',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
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
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check that desired code is not used by another product
        $colliding = $em->getRepository(Product::class)->findOneBy([
            'code' => $product->getCode()
        ]);

        // If match
        if ($colliding != null) {
            // Send bad request
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_already_used",
                    ["code" => $product->getCode()],
                    "errors"
                )
            ], Response::HTTP_BAD_REQUEST);
        }

        // Set auto generated product info
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());
        // Save product in DB
        $em->persist($product);
        $em->flush();

        // Redirect to created product URL
        return $this->redirectToRoute('product_show', [
            'id' => $product->getId(),
            '_locale' => $this->translator->getLocale()
        ], Response::HTTP_CREATED);
    }

    /** PATCH methods */

    /**
     * Update product info (admin only).
     * Expected payload :
     * {
     *  "code": "product code",
     *  "name": "product name",
     *  "description": "product description",
     *  "image": "product image URL",
     *  "category": "product category name",
     *  "price": productPrice (decimal),
     *  "quantity": productQuantity (integer),
     *  "internalReference": "product o",
     *  "shellId": 10,
     *  "inventoryStatus": "INSTOCK|LOWSTOCK|OUTOFSTOCK",
     *  "rating": productRating (decimal)
     *  }
     *
     * @param Product $newData Updated product data.
     * @param int $id Product ID to update.
     * @param EntityManagerInterface $em Used Entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_update',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['PATCH']
    )]
    public function update(
        #[MapRequestPayload(
            acceptFormat: "json",
            serializationContext: [
                'groups' => ['product.update']
            ]
        )]
        Product $newData,
        int $id,
        EntityManagerInterface $em
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->find($id);
        // If no match
        if (!$product) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.id_not_found",
                    ["id" => $id],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
        // Check that desired code is not used by another product
        $colliding = $em->getRepository(Product::class)->findOneBy([
            'code' => $newData->getCode()
        ]);

        // If match and not same product
        if ($colliding != null && $colliding->getId() != $product->getId()) {
            // Send bad request
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_already_used",
                    ["code" => $newData->getCode()],
                    "errors"
                )
            ], Response::HTTP_BAD_REQUEST);
        }

        // Merge product data
        $product->mergeNewData($newData);
        // Save updated info
        $em->persist($product);
        $em->flush();
        // Send updated product data
        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** DELETE methods */

    /**
     * Remove product from DB.
     *
     * @param int $id Product ID to delete
     * @param EntityManagerInterface $em Used entity manager.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_delete',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // Fetch wanted product in DB
        $product = $em->getRepository(Product::class)->find($id);
        // If no match
        if (!$product) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.id_not_found",
                    ["id" => $id],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
        // Delete product in DB
        $em->remove($product);
        $em->flush();

        // Send deleted product info
        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
}