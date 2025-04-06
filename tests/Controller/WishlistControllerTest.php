<?php

namespace Tests\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class regrouping all WishlistController tests.
 */
class WishlistControllerTest extends TestControllerBase
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
     * @var WishlistRepository Used product repostitory.
     */
    private WishlistRepository $wishlistRepository;

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
        $this->wishlistRepository = static::getContainer()->get(WishlistRepository::class);
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    /**
     * Test get wishlist failed when not logged in.
     * @return void
     */
    public function testGetWishlistShouldFailedWhenNotLoggedIn(): void
    {
        // Get wishlist
        $this->client->request('GET', '/fr/wishlist', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test wishlist sent is empty when user has no wishlist.
     * @return void
     */
    public function testGetReturnedWishlistIsEmptyWhenNotCreatedYet(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);
        // Perform action
        $this->client->request('GET', '/fr/wishlist', [], [], [
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

        // Test no wishlist has been created
        $wishlists = $this->wishlistRepository->findAll();
        $this->assertEquals(0, sizeof($wishlists));
    }

    /**
     * Test adding product to wishlist failed when not logged in.
     * @return void
     */
    public function testAddProductToWishlistShouldFailedWhenNotLoggedIn(): void
    {
        // Try adding product tp wishlist
        $this->client->request('POST', '/fr/wishlist/1', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test adding product to wishlist's user creates it and add desired product.
     * @return void
     */
    public function testaddProductToWishlistShouldSucceedWhenNoWishlist(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);
        // Get product to add in wishlist
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains only desired product
        $this->assertIsArray($json);
        $this->assertEquals(1, sizeof($json));
        $this->assertIsArray($json[0]);
        $this->assertArrayHasKey('code', $json[0]);
        $this->assertEquals($product->getCode(), $json[0]['code']);

        // Test wishlist has been created
        $wishlists = $this->wishlistRepository->findAll();
        $this->assertEquals(1, sizeof($wishlists));

        // Get user from DB
        $user = $this->userRepository->findOneByEmail($userDto['email']);
        // Check that created wishlist has been attached to user
        $wishlist = $user->getWishlist();
        $this->assertNotNull($wishlist);

        // Check that product wishlist has 1 item
        $this->assertEquals(1, sizeof($wishlist->getProducts()));

        // Check that product in wishlist is the one expected
        $this->assertEquals($product->getId(), $wishlist->getProducts()[0]->getId());

        // Get wishlist from server
        $this->client->request('GET', '/fr/wishlist', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array has 1 item
        $this->assertIsArray($json);
        $this->assertEquals(1, sizeof($json));
        // Test returned added product has expected code
        $this->assertEquals($product->getCode(), $json[0]['code']);
    }

    /**
     * Test adding unexisting product returns 404 error.
     * @return void
     */
    public function testAddUnexistingProductToWishlistShouldThrow404(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);

        // Perform action
        $this->client->request('POST', '/fr/wishlist/0', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        // Test HTTP response is not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test adding product already in wishlist do nothing.
     * @return void
     */
    public function testAddProductAlreadyInWishlistDoNothing(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);
        // Get product to add in wishlist
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Perform action twice
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array contains only desired product
        $this->assertIsArray($json);
        $this->assertEquals(1, sizeof($json));
        $this->assertIsArray($json[0]);
        $this->assertArrayHasKey('code', $json[0]);
        $this->assertEquals($product->getCode(), $json[0]['code']);

        // Test wishlist has been created
        $wishlists = $this->wishlistRepository->findAll();
        $this->assertEquals(1, sizeof($wishlists));

        // Get user from DB
        $user = $this->userRepository->findOneByEmail($userDto['email']);
        // Check that created wishlist has been attached to user
        $wishlist = $user->getWishlist();
        $this->assertNotNull($wishlist);
        // Check that product wishlist has 1 item
        $this->assertEquals(1, sizeof($wishlist->getProducts()));
        // Check that product in wishlist is the one expected
        $this->assertEquals($product->getId(), $wishlist->getProducts()[0]->getId());

        // Get wishlist from server
        $this->client->request('GET', '/fr/wishlist', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array has 1 item
        $this->assertIsArray($json);
        $this->assertEquals(1, sizeof($json));
        // Test returned added product has expected code
        $this->assertEquals($product->getCode(), $json[0]['code']);
    }

    /**
     * Test delete product from wishlist failed when not logged in.
     * @return void
     */
    public function testDeleteProductFromWishlistShouldFailedWhenNotLoggedIn(): void
    {

        // Perform action
        $this->client->request('DELETE', '/fr/wishlist/1', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
        // Assert response is unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test delete unexisting product from wishlist throw 404 error.
     * @return void
     */
    public function testDeleteUnexistingProductFromWishlistShouldThrow404(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);

        // Perform action
        $this->client->request('DELETE', '/fr/wishlist/0', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        // Test HTTP response is not found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test delete product not in wishlist do nothing.
     *
     * @throws Exception If test went wrong.
     */
    public function testDeleteProductNotInWishlistDoNothing(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);
        // Get product to add in wishlist
        $productDto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($productDto['code']);

        // Add product to wishlist
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        // Get product to remove from wishlist
        $removeDto = $this->data->getProducts()[1];
        $remove = $this->productRepository->findOneByCode($removeDto['code']);
        // Remove product from wishlist
        $this->client->request('DELETE', '/fr/wishlist/' . $remove->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array has 1 item
        $this->assertIsArray($json);
        $this->assertEquals(1, actual: sizeof($json));
        // Test returned added product has expected code
        $this->assertEquals($product->getCode(), $json[0]['code']);

        // Get user wishlist from DB
        $user = $this->userRepository->findOneByEmail($userDto['email']);
        $wishlist = $user->getWishlist();
        // Check that wishlist still has 1 product
        $this->assertEquals(1, sizeof($wishlist->getProducts()));
        // Check that product in wishlist is the one expected
        $this->assertEquals($product->getId(), $wishlist->getProducts()[0]->getId());
    }

    /**
     * Test delete product in wishlist succed.
     *
     * @throws Exception If test went wrong.
     */
    public function testDeleteProductInWishlistSucceed(): void
    {
        // Get user
        $userDto = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);
        // Get product to add in wishlist
        $productDto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($productDto['code']);

        // Add product to wishlist
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        // Remove product from wishlist
        $this->client->request('DELETE', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $response = $this->client->getResponse();
        // Test HTTP response is OK
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());

        $json = json_decode($response->getContent(), true);
        // Test returned array has no item
        $this->assertIsArray($json);
        $this->assertEquals(0, actual: sizeof($json));

        // Get user wishlist from DB
        $user = $this->userRepository->findOneByEmail($userDto['email']);
        $wishlist = $user->getWishlist();
        // Check that wishlist still has no product
        $this->assertEquals(0, sizeof($wishlist->getProducts()));
    }

    /**
     * Test delete product when wishlist is not created do nothing.
     * @return void
     */
    public function testDeleteProductWhenNoWishlistDoNothing(): void
    {
        // Get user
        $user = $this->data->getUsers()[1];
        // Get token
        $token = $this->getJwtToken($user['email'], $user['password']);

        // Get product to remove from wishlist
        $productDto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($productDto['code']);
        // Remove product from wishlist
        $this->client->request('DELETE', '/fr/wishlist/' . $product->getId(), [], [], [
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

        // Test no wishlist has been created
        $wishlists = $this->wishlistRepository->findAll();
        $this->assertEquals(0, sizeof($wishlists));
    }

    /**
     * Test that when a product is deleted, all wishlists having this product 
     * remove it.
     * @return void
     */
    public function testDeleteProductShouldRemovesItFromAllWishlists() : void 
    {
        // Get admin
        $userDto = $this->data->getUsers()[0];
        // Get token
        $token = $this->getJwtToken($userDto['email'], $userDto['password']);
        // Get product to add in wishlist
        $dto = $this->data->getProducts()[0];
        $product = $this->productRepository->findOneByCode($dto['code']);

        // Add product to wishlist
        $this->client->request('POST', '/fr/wishlist/' . $product->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

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

        // Get user wishlist from DB
        $user = $this->userRepository->findOneByEmail($userDto['email']);
        $wishlist = $user->getWishlist();
        // Check that wishlist has no product
        $this->assertEquals(0, sizeof($wishlist->getProducts()));
    }
}