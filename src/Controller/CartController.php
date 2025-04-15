<?php

namespace App\Controller;

use App\Service\CartService;
use App\Utils\Cart;
use App\Utils\CartItem;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller managing a user cart.
 */
class CartController extends AbstractController
{
    /**
     * @var CartService Used product service.
     */
    private CartService $cartService;

    /**
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    /**
     * @var string Cart items session key.
     */
    final protected string $CART_ITEM_SESSION_KEY = "cartItems";

    /**
     * Generate controller.
     *
     * @param CartService $cartService Used product service.
     * @param TranslatorInterface $translator Used translator.
     */
    public function __construct(
        CartService $cartService,
        TranslatorInterface $translator
    ) {
        $this->cartService = $cartService;
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
    public function index(Request $request): Response
    {
        // Get user cart
        $cart = $this->getCart($request);

        // Return user cart
        return $this->json(
            $cart->getItems(),
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }


    /**
     * Add product to cart.
     * Required payload :
     * {
     *      "productCode": Product code to add (string)
     *      "quantity": Quantity to add (int)
     * }
     *
     * @param Request $request Client request.
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
    public function add(Request $request): Response
    {
        // Get request payload
        $payload = $request->getPayload();

        // Validate payload quantity
        $quantity = $payload->get("quantity");
        // If invalid quantity (not an int)
        if (!is_int($quantity)) {
            // Send error
            return $this->json([
                'error' => $this->translator->trans(
                    "cart.payload.invalid_quantity",
                    [],
                    "errors"
                ),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get product code
        $productCode = $payload->get("productCode");

        // Get cart
        $cart = $this->getCart($request);

        try {
            // Add product to cart
            $this->cartService->add($cart, $productCode, $quantity);
        }
        // If product not found
        catch (InvalidArgumentException) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $productCode],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
        // Save cart
        $this->saveCart($request, $cart);

        // Send result
        $result = [
            "cart" => $cart->getItems(),
            "errors" => $cart->getErrors()
        ];

        return $this->json(
            $result,
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }

    /**
     * Remove product from cart.
     * @param Request $request User request.
     * @param string $code Desired product code.
     * @return Response Updated cart, or error if something went wrong.
     */
    #[Route(
        '/{_locale}/cart/{code}',
        name: 'cart_remove',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function delete(Request $request, string $code): Response
    {
        // Get user cart
        $cart = $this->getCart($request);

        // Remove product from cart
        try {
            $this->cartService->remove($cart, $code);
        }
        // If product not found
        catch (InvalidArgumentException) {
            // Send 404 error
            return $this->json([
                'error' => $this->translator->trans(
                    "product.code_not_found",
                    ["code" => $code],
                    "errors"
                )
            ], Response::HTTP_NOT_FOUND);
        }
        // Save cart
        $this->saveCart($request, $cart);

        // Send result
        $result = [
            "cart" => $cart->getItems(),
            "errors" => []
        ];

        return $this->json(
            $result,
            200,
            [],
            [
                'groups' => ['product.index']
            ]
        );
    }

    /**
     * Get user cart.
     * @param Request $request User request.
     * @return Cart Corresponding cart.
     */
    private function getCart(Request $request): Cart
    {
        // Get user session
        $session = $request->getSession();

        $cartItems = [];
        // If cart items defined in session
        if ($session->has($this->CART_ITEM_SESSION_KEY)) {
            $cartItems = $session->get($this->CART_ITEM_SESSION_KEY);
        }

        // Return corresponding cart
        return new Cart($cartItems);
    }

    /**
     * Save user cart.
     * @param Request $request User request.
     * @param Cart $cart User cart.
     * @return void
     */
    private function saveCart(Request $request, Cart $cart): void
    {
        $session = $request->getSession();
        $session->set($this->CART_ITEM_SESSION_KEY, $cart->getItems());
    }
}