<?php

namespace Tests\Integration;

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
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        parent::setUp();
        // Load used attributes
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
                'productCode' => $dto['code'],
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
        $this->assertArrayHasKey('code', $cartItem['product']);
        $this->assertEquals($dto['code'], $cartItem['product']['code']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals(1, $cartItem['quantity']);
    }

    /**
     * Test adding non-existing product returns 404 error.
     * @return void
     */
    public function testAddNonExistingProductToCartShouldThrow404(): void
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
                'productCode' => 0,
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
                'product.code_not_found',
                [
                    'code' => 0
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
    public function testAddInvalidQuantityToCartShouldThrow400(): void
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
                'productCode' => 0,
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
                'productCode' => $dto['code'],
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
                'productCode' => $dto['code'],
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
        $this->assertArrayHasKey('code', $cartItem['product']);
        $this->assertEquals($dto['code'], $cartItem['product']['code']);
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
                'productCode' => $dto['code'],
                'quantity' => $dto['quantity'] + 1
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
                    'code' => $dto['code'],
                    'stock' => $dto['quantity']
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
        $this->assertArrayHasKey('code', $cartItem['product']);
        $this->assertEquals($dto['code'], $cartItem['product']['code']);
        $this->assertArrayHasKey('quantity', $cartItem);
        $this->assertEquals($dto['quantity'], $cartItem['quantity']);
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
                'productCode' => $dto['code'],
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
                    'code' => $dto['code']
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
                'product.code_not_found',
                [
                    'code' => 0
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
        $removedDto = $this->data->getProducts()[1];

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
                'productCode' => $addedDto['code'],
                'quantity' => 1
            ])
        );
        // Remove other product
        $this->client->request(
            'DELETE',
            '/fr/cart/' . $removedDto['code'],
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
        $this->assertArrayHasKey('code', $cartItem['product']);
        $this->assertEquals($addedDto['code'], $cartItem['product']['code']);
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
                'productId' => $dto['code'],
                'quantity' => 1
            ])
        );
        // Remove other product
        $this->client->request(
            'DELETE',
            '/fr/cart/' . $dto['code'],
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