<?php

namespace Tests\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class regrouping all ProductController tests.
 */
class ProductControllerTest extends TestControllerBase
{
    /**
     * @var ProductRepository Used product repostitory.
     */
    private ProductRepository $productRepository;

    /**
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        parent::setUp();
        // Load user repository
        $this->productRepository = static::getContainer()->get(ProductRepository::class);
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    /**
     * Check that login is required to get all products.
     * @return void
     */
    public function testGetAllProductsShouldFailedWhenNotLoggedIn(): void
    {
        // Get product list
        $this->client->request('GET', '/fr/product');
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
        foreach ($this->productRepository->findAll() as $product) {
            $productCodes[] = $product->getCode();
        }

        // Get product list
        $this->client->request('GET', '/fr/product', [], [], [
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
        $this->client->request('GET', '/fr/product/1');
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
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Get product details
        $this->client->request('GET', '/fr/product/' . $product->getId(), [], [], [
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
     * Test getting unexisting product failed with 404.
     * @return void.
     */
    public function testGetUnexsitingProductShouldThrow404(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Try getting unexisting product
        $this->client->request('GET', '/fr/product/0', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        // Assert response is not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.id_not_found',
                [
                    'id' => 0
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
        $this->client->request('POST', '/fr/product');
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
        $this->client->request('POST', '/fr/product', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

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
        $this->client->request('POST', '/fr/product', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

        $response = $this->client->getResponse();
        // Test HTTP response is Created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        // Test that we are redirected to product details URL
        $this->assertTrue($response->isRedirect());
        $redirectedUrl = $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#^/fr/product/\d+$#', $redirectedUrl);

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
        $this->client->request('POST', '/fr/product', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode($dto));

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
            '/fr/product/1',
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
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Create product
        $this->client->request('PATCH', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

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
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Change description
        $dto['description'] = "Updated description";

        // Create product
        $this->client->request('PATCH', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

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


        // Reload product from DB
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Check that product description has been updated
        $this->assertEquals($dto['description'], $product->getDescription());
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
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Change code
        $dto['code'] = "Code updated";

        // Create product
        $this->client->request('PATCH', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

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

        // Reload product from DB
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Check that product description has been updated
        $this->assertEquals($dto['code'], $product->getCode());
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
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Get last update date
        $lastUpdate = $product->getUpdatedAt();

        // Create product
        $this->client->request('PATCH', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($dto));

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

        // Reload product from DB
        $product = $this->productRepository->findOneByCode($dto['code']);
        // Check that product has been updated
        $this->assertGreaterThan($lastUpdate, $product->getUpdatedAt());
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
        $updatedProduct = $this->productRepository->findOneByCode($updatedDto['code']);
        $collidingDto = $this->data->getProducts()[1];
        $collidingProduct = $this->productRepository->findOneByCode($collidingDto['code']);
        // Set updated DTO code to colliding DTO
        $updatedDto['code'] = $collidingProduct->getCode();


        // Create product
        $this->client->request('PATCH', '/fr/product/' . $updatedProduct->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($updatedDto));

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
                    'code' => $updatedDto['code']
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
    public function testUpdateProductShouldFailedIfNotExists() : void {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test products
        $updatedDto = $this->data->getProducts()[0];

        // Create product
        $this->client->request('PATCH', '/fr/product/0', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($updatedDto));

        $response = $this->client->getResponse();
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.id_not_found',
                [
                    'id' => 0
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
    public function testDeleteProductShouldFailedWhenNotLoggedIn() : void {
        // Update product
        $this->client->request(
            'DELETE',
            '/fr/product/1',
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
    public function testDeleteProductShouldFailedWhenNotAdmin() : void {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Delete product
        $this->client->request('DELETE', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        // Test HTTP response is forbidden
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        // Check that product has not been deleted
        $product = $this->productRepository->findOneByCode($dto['code']);
        $this->assertNotNull($product);
    }
    
    /**
     * Check that admin can delete product.
     * @return void.
     */
     public function testDeleteProductShouldSuccedIfAdmin() : void {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get test product
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Delete product
         $this->client->request('DELETE', '/fr/product/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Check that product has been deleted
        $product = $this->productRepository->findOneByCode($dto['code']);
        $this->assertNull($product);
    }

    /**
     * Check that admin cannot delete product if product id does not exists.
     * @return void.
     */
    public function testDeleteProductShouldFailedIfNotExists() : void {
        // Get user
        $user = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Delete product
         $this->client->request('DELETE', '/fr/product/0', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'product.id_not_found',
                [
                    'id' => 0
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error']
        );
    }
}