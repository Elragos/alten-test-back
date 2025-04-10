<?php

namespace Tests\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class regrouping all CartController tests.
 */
class CartControllerTest extends TestControllerBase
{
    /**
     * @var ProductRepository Used product repostitory.
     */
    private ProductRepository $productRepository;

    /**
     * @var UserRepository Used user repository.
     */
    private UserRepository $userRepository;

    /**
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        parent::setUp();
        // Load used attributes
        $this->productRepository = static::getContainer()->get(ProductRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }
    /**
     * Test get cart failed when not logged in.
     * @return void
     */
    public function testGetCartShouldFailedWhenNotLoggedIn(): void
    {
        // Perform action
        $this->client->request('GET', '/fr/cart', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test cart sent is empty when user has done nothing.
     * @return void
     */
    public function testGetCartIsEmptyWhenSessionInitialized(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Perform action
        $this->client->request('GET', '/fr/cart', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array is empty
        $this->assertIsArray($json);
        $this->assertEquals(0, sizeof($json));
    }

    /**
     * Test adding product to cart failed when not logged in.
     * @return void
     */
    public function testAddProductToCartShouldFailedWhenNotLoggedIn(): void
    {

        // Perform action
        $this->client->request('POST', '/fr/cart', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test adding product to wishlist is successful.
     * @return void
     */
    public function testAddProductToCartShouldSucceed(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get product to add in cart
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => 1
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list is empty
        $this->assertEquals(0, sizeof($json['errors']));
        // Test cart item has 1 item with desired product and quantity
        $this->assertEquals(1, sizeof($json['cart']));
        $cartItem = $json['cart'][0];
        $this->assertArrayHasKey('product', $cartItem);
        $this->assertArrayHasKey('id', $cartItem['product']);
        $this->assertEquals($product->getId(), $cartItem['product']['id']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals(1, $cartItem['quantity']);
    }

    /**
     * Test adding unexisting product returns 404 error.
     * @return void
     */
    public function testaddUnexistingProductToCartShouldThrow404(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => 0,
                'quantity' => 1
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals(
            $this->translator->trans(
                'product.id_not_found',
                [
                    'id' => 0
                ],
                'errors',
                'fr'
            ),
            $json['error']
        );
    }

    /**
     * Test adding invalid productId returns 400 error.
     * @return void
     */
    public function testAddInvalidProductIdToCartShouldThrow400(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => 'azerty',
                'quantity' => 1
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals(
            $this->translator->trans(
                'cart.payload.invalid_product_id',
                [],
                'errors',
                'fr'
            ),
            $json['error']
        );
    }

    /**
     * Test adding invalid productId returns 400 error.
     * @return void
     */
    public function testaddInvalidQuantityToCartShouldThrow400(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => 1,
                'quantity' => 'azerty'
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals(
            $this->translator->trans(
                'cart.payload.invalid_quantity',
                [],
                'errors',
                'fr'
            ),
            $json['error']
        );
    }

    /**
     * Test adding product already in cart add quantity.
     * @return void
     */
    public function testAddProductAlreadyInCartAddQuantity(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get product to add in cart
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action twice
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => 1
            ])
        );
        // Perform action twice
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => 1
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list is empty
        $this->assertEquals(0, sizeof($json['errors']));
        // Test cart item has 1 item with desired product and quantity
        $this->assertEquals(1, sizeof($json['cart']));
        $cartItem = $json['cart'][0];
        $this->assertArrayHasKey('product', $cartItem);
        $this->assertArrayHasKey('id', $cartItem['product']);
        $this->assertEquals($product->getId(), $cartItem['product']['id']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals(2, $cartItem['quantity']);
    }

    /**
     * Test adding more that product stock limits it to product stock.
     * @return void
     */
    public function testAddMoreThanProductStockLimitsItToStock(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get product to add in cart
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => $product->getQuantity() + 1
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list has expected error
        $this->assertEquals(1, sizeof($json['errors']));
        $this->assertEquals(
            $this->translator->trans(
                'cart.item.not_enough_stock',
                [
                    'productId' => $product->getId(),
                    'productStock' => $product->getQuantity(),
                ],
                'errors',
                'fr'
            ),
            $json['errors'][0]
        );
        // Test cart item has 1 item with desired product and quantity
        $this->assertEquals(1, sizeof($json['cart']));
        $cartItem = $json['cart'][0];
        $this->assertArrayHasKey('product', $cartItem);
        $this->assertArrayHasKey('id', $cartItem['product']);
        $this->assertEquals($product->getId(), $cartItem['product']['id']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals($product->getQuantity(), $cartItem['quantity']);
    }

    /**
     * Test that, when cart item has zero quantity, item is removes and a cart 
     * error is generated.
     * @return void
     */
    public function testZeroQuantityInCartRemovesProductAndGeneratesError(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get product to add in cart
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => 0
            ])
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list has expected error
        $this->assertEquals(1, sizeof($json['errors']));
        $this->assertEquals(
            $this->translator->trans(
                'cart.item.quantity_zero',
                [
                    'productId' => $product->getId(),
                    'productStock' => $product->getQuantity(),
                ],
                'errors',
                'fr'
            ),
            $json['errors'][0]
        );
        // Test cart has no item
        $this->assertEquals(0, sizeof($json['cart']));
    }

    /**
     * Test delete product from cart failed when not logged in.
     * @return void
     */
    public function testDeleteProductFromCartShouldFailedWhenNotLoggedIn() : void
    {    
        // Perform action
        $this->client->request('DELETE', '/fr/cart/0', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    
    /**
     * Test delete non-existing product from cart throw 404 error.
     * @return void
     */
    public function testDeleteNonExistingProductFromCartShouldThrow404() : void {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Perform action
        $this->client->request(
            'DELETE',
            '/fr/cart/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $response = $this->client->getResponse();
        // Test HTTP response is Not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals(
            $this->translator->trans(
                'product.id_not_found',
                [
                    'id' => 0
                ],
                'errors',
                'fr'
            ),
            $json['error']
        );
    }
    
    /**
     * Test delete product not in cart do nothing.
     * @return void
     */
    public function testDeleteProductNotInCartDoNothing() : void {
       // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get test products
        $addedDto = $this->data->getProducts()[0];
        $addedProduct = $this->productRepository->findOneByCode($addedDto['code']);
        $removedDto = $this->data->getProducts()[1];
        $removedProduct = $this->productRepository->findOneByCode($removedDto['code']);

        // Add product
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $addedProduct->getId(),
                'quantity' => 1
            ])
        );
        // Remove other product
        $this->client->request(
            'DELETE',
            '/fr/cart/' . $removedProduct->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list is empty
        $this->assertEquals(0, sizeof($json['errors']));
        // Test cart item has 1 item with desired product and quantity
        $this->assertEquals(1, sizeof($json['cart']));
        $cartItem = $json['cart'][0];
        $this->assertArrayHasKey('product', $cartItem);
        $this->assertArrayHasKey('id', $cartItem['product']);
        $this->assertEquals($addedProduct->getId(), $cartItem['product']['id']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals(1, $cartItem['quantity']);
    }
    
    /**
     * Test delete product in cart succeed.
     * @return void
     */
    public function testDeleteProductInCartSucceed() : void {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Get test products
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Add product
        $this->client->request(
            'POST',
            '/fr/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'productId' => $product->getId(),
                'quantity' => 1
            ])
        );
        // Remove other product
        $this->client->request(
            'DELETE',
            '/fr/cart/' . $product->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains 2 item
        $this->assertIsArray($json);
        $this->assertArrayHasKey('cart', $json);
        $this->assertArrayHasKey('errors', $json);
        // Test error list is empty
        $this->assertEquals(0, sizeof($json['errors']));
        // Test cart item is empty
        $this->assertEquals(0, sizeof($json['cart']));
    }
}