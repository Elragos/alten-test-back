<?php

namespace Tests\Integration;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class regrouping all ProductController tests.
 */
class ProductControllerTest extends TestControllerBase
{

    /**
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        parent::setUp();
        // Load user repository
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    /**
     * Check that login is required to get all products.
     * @return void
     */
    public function testGetAllProductsShouldFailedWhenNotLoggedIn(): void
    {
        // Get product list
        $this->client->request('GET', '/fr/products');
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test getting all products when authentified.
     * @return void
     */
    public function testGetAllProductsShouldSucceedWith3Products(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test products' code
        $productCodes = [];
        foreach ($this->data->getProducts() as $product) {
            $productCodes[] = $product['code'];
        }

        // Get product list
        $this->client->request('GET', '/fr/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test list has 3 items
        $this->assertIsArray($json);
        $this->assertSameSize($productCodes, $json);
        // Test list contains all desired codes
        foreach ($json as $productData) {
            $this->assertArrayHasKey('code', $productData);
            $this->assertContains($productData['code'], $productCodes);
        }
    }

    /**
     * Check that login is required to get specific product.
     * @return void
     */
    public function testGetSpecificProductShouldFailedWhenNotLoggedIn(): void
    {
        // Get product list
        $this->client->request('GET', '/fr/products/1');
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test getting specific product.
     * @return void.
     */
    public function testGetSpecificProductShouldSucceed(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product code
        $dto = $this->data->getProducts()[0];

        // Get product details
        $this->client->request('GET', '/fr/products/' . $dto['code'], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test sent product is the one expected
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals($json['code'], $dto['code']);
    }

    /**
     * Test getting non-existing product failed with 404.
     * @return void.
     */
    public function testGetNonExsitingProductShouldThrow404(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Try getting unexisting product
        $this->client->request(
            'GET',
            '/fr/products/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        $response = $this->client->getResponse();
        // Assert response is not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.code_not_found',
                [
                    'code' => 0
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }

    /**
     * Check that login is required to create product.
     * @return void.
     */
    public function testCreateProductShouldFailedWhenNotLoggedIn(): void
    {
        // Create product
        $this->client->request('POST', '/fr/products');
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    /**
     * Check that admin is required to create product.
     * @return void.
     */
    public function testCreateProductShouldFailedWhenNotAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product code
        $dto = $this->data->getProducts()[0];

        // Create product
        $this->client->request(
            'POST',
            '/fr/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($dto)
        );

        // Test HTTP response is forbidden
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Check that admin can create new product.
     * @return void.
     */
    public function testCreateProductShouldSucceedWhenAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product code
        $dto = $this->data->getProducts()[0];
        // Change product code to avoid conflict
        $dto['code'] = 'Test creation';

        // Create product
        $this->client->request(
            'POST',
            '/fr/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($dto)
        );

        $response = $this->client->getResponse();
        // Test HTTP response is Created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        // Test that we are redirected to product details URL
        $this->assertTrue($response->isRedirect());
        $redirectedUrl = $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#^/fr/products/\S+$#', $redirectedUrl);

        // Access redirected URL
        $this->client->request('GET', $redirectedUrl, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test sent data is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test sent product is the one expected
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals($json['code'], $dto['code']);
    }

    /**
     * Check that duplicate products with same code is forbidden.
     * @return void.
     */
    public function testCreateExistingProductShouldFailed(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product code
        $dto = $this->data->getProducts()[0];

        // Create product
        $this->client->request(
            'POST',
            '/fr/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($dto)
        );

        $response = $this->client->getResponse();
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.code_already_used',
                [
                    'code' => $dto['code']
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }

    /**
     * Check that login is required to update product.
     * @return void.
     */
    public function testUpdateProductShouldFailedWhenNotLoggedIn(): void
    {
        // Update product
        $this->client->request(
            'PATCH',
            '/fr/products/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Check that admin is required to update product.
     * @return void.
     */
    public function testUpdateProductShouldFailedWhenNotAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];

        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/' . $dto['code'],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($dto)
        );

        // Test HTTP response is forbidden
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
    /**
     * Check that admin can update product.
     * @return void.
     */
    public function testUpdateProductShouldSucceedWhenAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];
        // Change description
        $dto['description'] = "Updated description";

        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/' . $dto['code'],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($dto)
        );

        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test sent data is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test sent product is the one expected
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals($json['code'], $dto['code']);
        // Test that description matches the one sent
        $this->assertArrayHasKey('description', $json);
        $this->assertEquals($json['description'], $dto['description']);
    }

    /**
     * Check that admin can update product code if product code is not used.
     * @return void.
     */
    public function testUpdateProductCodeShouldSucceedIfNotUsed(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];
        $code = $dto['code'];
        // Change code
        $dto['code'] = "Code updated";

        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/' . $code,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($dto)
        );

        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test sent data is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test sent product is the one expected
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals($json['code'], $dto['code']);
        // Test that description matches the one sent
        $this->assertArrayHasKey('description', $json);
        $this->assertEquals($json['description'], $dto['description']);
    }

    /**
     * Check that admin can update product code if product updated 
     * is the one using that code.
     *
     * @return void.
     */
    public function testUpdateProductCodeShouldSucceedIfSameProduct(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];

        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/' . $dto['code'],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($dto)
        );

        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test sent data is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test sent product is the one expected
        $this->assertArrayHasKey('code', $json);
        $this->assertEquals($json['code'], $dto['code']);
        // Test that description matches the one sent
        $this->assertArrayHasKey('description', $json);
        $this->assertEquals($json['description'], $dto['description']);
    }

    /**
     * Check that admin cannot update product code if code used by 
     * another product.
     * @return void.
     */
    public function testUpdateProductCodeShouldFailedIfAlreadyUsed(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test products
        $updatedDto = $this->data->getProducts()[0];
        $code = $updatedDto['code'];
        $collidingDto = $this->data->getProducts()[1];
        // Set updated DTO code to colliding DTO
        $updatedDto['code'] = $collidingDto['code'];

        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/' . $code,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($updatedDto)
        );
        $response = $this->client->getResponse();
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.code_already_used',
                [
                    'code' => $code
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }

    /**
     * Check that admin cannot update product if product id does not exists.
     * @return void.
     */
    public function testUpdateProductShouldFailedIfNotExists(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test products
        $updatedDto = $this->data->getProducts()[0];

        // Create product
        // Create product
        $this->client->request(
            'PATCH',
            '/fr/products/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($updatedDto)
        );

        $response = $this->client->getResponse();
        // Assert that response is not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.code_not_found',
                [
                    'code' => 0
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }

    /**
     * Check that login is required to delete product.
     * @return void.
     */
    public function testDeleteProductShouldFailedWhenNotLoggedIn(): void
    {
        // Update product
        $this->client->request(
            'DELETE',
            '/fr/products/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Check that admin is required to delete product.
     *
     * @return void.
     */
    public function testDeleteProductShouldFailedWhenNotAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Delete product
        $this->client->request(
            'DELETE',
            '/fr/products/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Test HTTP response is forbidden
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Check that admin can delete product.
     * @return void.
     */
    public function testDeleteProductShouldSuccedIfAdmin(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];

        // Delete product
        $this->client->request(
            'DELETE',
            '/fr/products/' . $dto['code'],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
    }

    /**
     * Check that admin cannot delete product if product id does not exists.
     * @return void.
     */
    public function testDeleteProductShouldFailedIfNotExists(): void
    {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Delete product
        $this->client->request(
            'DELETE',
            '/fr/products/0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        $response = $this->client->getResponse();
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.code_not_found',
                [
                    'code' => 0
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }
}