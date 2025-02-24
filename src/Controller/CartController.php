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
        if (!$session->has('cart'))
        {
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
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function add(Request $request, EntityManagerInterface $em) : Response
    {
        // Get user session
        $session = $request->getSession();

        // Initialize cart if not defined
        if (!$session->has('cart'))
        {
            $session->set('cart', []);
        }

        // Get request payload
        $payload = $request->getPayload();
        // Validate payload product (product exists in DB)
        $productId = $payload->get("productId");
        // If productId not supplied
        if (!$productId)
        {
            // Send error
            return $this->json([
                'error' => $this->translator->trans("cart.payload.product_id_missing", [], "errors"),
            ], 400, [], []);
        }
        // Fetch wanted product with DB
        $product = $em->getRepository(Product::class)->find($productId);
        // If not found
        if (!$product)
        {
            // Send 404 error
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $productId], "errors")
            );
        }
        // Validate payload quantity
        $quantity = $payload->get("quantity");
        // If invalid quantity (not an int)
        if (!is_int($quantity))
        {
            // Send error
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