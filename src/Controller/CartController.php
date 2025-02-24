<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartController extends AbstractController
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
        '/{_locale}/cart',
        name: 'cart_index',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function index(Request $request) : Response
    {
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart'))
        {
            $session->set('cart', []);
        }

        return $this->json($session->get('cart'));
    }

    #[Route(
        '/{_locale}/cart',
        name: 'cart_add',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function add(Request $request, EntityManagerInterface $em) : Response
    {
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart'))
        {
            $session->set('cart', []);
        }

        $payload = $request->getPayload();
        // Validate payload product
        $productId = $payload->get("productId");
        if (!$productId)
        {
            return $this->json([
                'error' => $this->translator->trans("cart.payload.product_id_missing", [], "errors"),
            ], 400, [], []);
        }
        $product = $em->getRepository(Product::class)->find($productId);
        if (!$product)
        {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $productId], "errors")
            );
        }
        // Validate payload quantity
        $quantity = $payload->get("quantity");
        if (!is_int($quantity))
        {
            return $this->json([
                'error' => $this->translator->trans("cart.payload.invalid_quantity", [], "errors"),
            ], 400, [], []);
        }

        // Find cart item accordingly
        $cart = $session->get('cart');
        // Initialize cart item if not set
        if (!isset($cart["$productId"]))
        {
            $cart["$productId"] = [
                "product" => $product,
                "quantity" => 0,
            ];
        }
        // Update cart item accordingly
        $errors = [];
        $cart["$productId"]["quantity"] += $quantity;
        // Limit quantity to product stock
        $productStock = $product->getQuantity();
        if ($cart["$productId"]["quantity"] > $productStock)
        {
            $cart["$productId"]["quantity"] = $productStock;
            $errors[] = $this->translator->trans("cart.payload.invalid_quantity", [
                "productId" => $productId,
                "quantity" => $productStock
            ], "errors");
        }
        // Remove item if quantity negative or null
        if ($cart["$productId"]["quantity"] <= 0)
        {
            unset($cart["$productId"]);
        }

        // Send result
        $session->set('cart', $cart);
        $result = [
            "cart" => $cart,
            "errors" => $errors
        ];

        return $this->json($result, 201, [], [
            'groups' => ['product.index']
        ]);
    }
}