<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing app products.
 */
class ProductController extends AbstractController
{

    /**
     * @var ProductService Used product service.
     */
    private ProductService $productService;

    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    /**
     * Generate controller.
     *
     * @param ProductService $productService Used product service.
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(
        ProductService $productService,
        TranslatorInterface $translator
    ) {
        $this->productService = $productService;
        $this->translator = $translator;
    }
    /** GET methods */

    /**
     * List available products.
     *
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/products',
        name: 'product_index',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function index(): Response
    {
        // Get all products from DB
        $products = $this->productService->getAll();

        // Send product list
        return $this->json($products, 200, [], [
            // Only send useful info for listing
            'groups' => ['product.index']
        ]);
    }

    /**
     * Get all info on a specific product.
     *
     * @param string $code Desired product code.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/products/{code}',
        name: 'product_show',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function show(string $code): Response
    {
        // Fetch wanted product with DB
        $product = $this->productService->getByCode($code);

        // If no match
        if ($product == null) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
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
     * @return Response Server Response (Redirect if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/products',
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
        Product $product
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Try creating product
        try {
            $this->productService->create($product);
        }
        // If code already used
        catch (InvalidArgumentException $e) {
            // Send bad request
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_already_used",
                    ["code" => $product->getCode()],
                    "errors"
                )
            ], Response::HTTP_BAD_REQUEST);
        }

        // Redirect to created product URL
        return $this->redirectToRoute('product_show', [
            'code' => $product->getCode(),
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
        '/{_locale}/products/{code}',
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
        string $code
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $product = $this->productService->update($code, $newData);
        } catch (InvalidArgumentException $e) {
            // Send bad request
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_already_used",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_BAD_REQUEST);
        } catch (EntityNotFoundException $e) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }

        // Send updated product data
        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** DELETE methods */

    /**
     * Remove product from DB.
     *
     * @param string $code Product code to remove.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/products/{code}',
        name: 'product_delete',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function delete(
        string $code
    ): Response {
        // Deny access if not admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        try{
            $product = $this->productService->delete($code);
        }
        catch (EntityNotFoundException $e) {
            // Send not found response
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
  
        // Send deleted product info
        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
}