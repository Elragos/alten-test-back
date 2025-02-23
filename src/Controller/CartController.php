<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class CartController extends AbstractController
{

    /** GET methods */

    #[Route(
        '/cart',
        name: 'cart_index',
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
        '/cart',
        name: 'cart_add',
        requirements: ['id' => Requirement::DIGITS],
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
            return $this->json(['error' => "No productId found in payload"], 400, [], []);
        }
        $product = $em->getRepository(Product::class)->find($productId);
        if (!$product)
        {
            throw $this->createNotFoundException(
                'No product found for id '.$productId
            );
        }
        // Validate payload quantity
        $quantity = $payload->get("quantity");
        if (!is_int($quantity))
        {
            return $this->json(['error' => "Invalid quantity in payload"], 400, [], []);
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
            $errors[] = "Not enough stock for Product $productId. Setting quantity at $productStock";
        }
        // Remove item if quantity negative or null
        if ($cart["$productId"]["quantity"] <= 0)
        {
            unset($cart["$productId"]);
        }

        // Send result
        $result = [
            "cart" => $cart,
            "errors" => $errors
        ];

        return $this->json($result, 201, [], [
            'groups' => ['product.index']
        ]);
    }
}