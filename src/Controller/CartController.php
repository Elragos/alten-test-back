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

/**
 * Controller managing a user cart.
 */
class CartController extends AbstractController
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
     * List items in cart.
     *
     * @param Request $request Client request.
     * @return Response Server Response (JSON if ok, error otherwise).
     */
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
        // Get user session.
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart')) {
            $session->set('cart', []);
        }

        // Return user cart
        return $this->json($session->get('cart'));
    }

    /**
     * Add product to cart.
     * Required payload :
     * {
     *      "productId": Product ID to add
     *      "quantity": quantity to add
     * }
     *
     * @param Request $request Client request.
     * @param EntityManagerInterface $em Used entity manager.
     * @return Response Server response (JSON if ok, error otherwise).
     */
    #[Route(
        '/{_locale}/cart',
        name: 'cart_add',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // Get request payload
        $payload = $request->getPayload();

        // Validate payload quantity
        $quantity = $payload->get("quantity");
        // If invalid quantity (not an int)
        if (!is_int($quantity)) {
            // Send error
            return $this->json([
                'error' => $this->translator->trans("cart.payload.invalid_quantity", [], "errors"),
            ], 400, [], []);
        }

        // Validate payload product (product exists in DB)
        $productId = $payload->get("productId");
        // If productId invalid (not an int)
        if (!is_int($productId)) {
            // Send error
            return $this->json([
                'error' => $this->translator->trans("cart.payload.invalid_product_id", [], "errors"),
            ], 400, [], []);
        }
        // Fetch wanted product with DB
        $product = $em->getRepository(Product::class)->find($productId);
        // If not found
        if (!$product) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.id_not_found",
                    ["id" => $productId],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }

        // Get user session
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart')) {
            $session->set('cart', []);
        }

        // Find cart item accordingly
        $cart = $session->get('cart');
        $cartIndexToUpdate = -1;
        foreach ($cart as $index => $cartItem) {
            if ($cartItem['product']->getId() == $productId) {
                $cartIndexToUpdate = $index;
            }
        }
        // Initialize cart item if not set
        if ($cartIndexToUpdate == -1) {
            $cart[] = [
                "product" => $product,
                "quantity" => 0,
            ];
            $cartIndexToUpdate = sizeof($cart) - 1;
        }
        // Update cart item accordingly
        $errors = [];
        $cart[$cartIndexToUpdate]["quantity"] += $quantity;
        // Limit quantity to product stock
        $productStock = $product->getQuantity();
        if ($cart[$cartIndexToUpdate]["quantity"] > $productStock) {
            $cart[$cartIndexToUpdate]["quantity"] = $productStock;
            $errors[] = $this->translator->trans("cart.item.not_enough_stock", [
                "productId" => $productId,
                "productStock" => $productStock
            ], "errors");
        }
        // Remove item if quantity negative or null
        if ($cart[$cartIndexToUpdate]["quantity"] <= 0) {
            unset($cart[$cartIndexToUpdate]);
            $errors[] = $this->translator->trans("cart.item.quantity_zero", [
                "productId" => $productId,
                "productStock" => $productStock
            ], "errors");
        }

        // Send result
        $session->set('cart', $cart);
        $result = [
            "cart" => $cart,
            "errors" => $errors
        ];

        return $this->json($result, 200, [], [
            'groups' => ['product.index']
        ]);
    }

    #[Route(
        '/{_locale}/cart/{productId}',
        name: 'cart_remove',
        requirements: [
            'productId' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function delete(Request $request, EntityManagerInterface $em, int $productId): Response
    {
        // If productId invalid (not an int)
        if (!is_int($productId)) {
            // Send error
            return $this->json([
                'error' => $this->translator->trans("cart.payload.invalid_product_id", [], "errors"),
            ], 400, [], []);
        }
        // Fetch wanted product with DB
        $product = $em->getRepository(Product::class)->find($productId);
        // If not found
        if (!$product) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.id_not_found",
                    ["id" => $productId],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }

        // Get user session
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart')) {
            $session->set('cart', []);
        }

        // Find cart item accordingly
        $cart = $session->get('cart');
        foreach ($cart as $index => $cartItem) {
            if ($cartItem['product']->getId() == $productId) {
                unset($cart[$index]);
            }
        }
       
        // Send result
        $session->set('cart', $cart);
        $result = [
            "cart" => $cart,
            "errors" => []
        ];

        return $this->json($result, 200, [], [
            'groups' => ['product.index']
        ]);
    }
}