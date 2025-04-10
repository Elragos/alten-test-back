<?php

namespace Tests\Integration;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class regrouping all UserController tests.
 */
class UserControllerTest extends TestControllerBase
{
    /**
     * @var UserRepository Used user repostitory.
     */
    private UserRepository $userRepository;

    /**
     * @var TranslatorInterface Used translator.
     */
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        parent::setUp();
        // Load user repository
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    /**
     * Test that login is successful.
     * @return void
     */
    public function testLoginShouldSucceed(): void
    {
        // Login with admin access
        $this->client->request(
            'POST',
            '/fr/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'email' => $this->data->getUsers()[0]['email'],
                'password' => $this->data->getUsers()[0]['password'],
            ])
        );

        $response = $this->client->getResponse();
        // Check response is successful
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());
        // Decode response
        $jsonResponse = json_decode($response->getContent(), true);
        // Test that JWT token was sent
        $this->assertArrayHasKey('token', $jsonResponse);
        // Test that JWT token is not empty
        $this->assertNotEmpty($jsonResponse["token"]);
    }

    /**
     * Test that user is logeged as user.
     * @return void
     */
    public function testUserLoginIsLoggedAsUser(): void
    {
        // Get user JWT token
        $email = $this->data->getUsers()[1]["email"];
        $password = $this->data->getUsers()[1]["password"];
        $token = $this->getJwtToken($email, $password);
        // Get user information
        $this->client->request('GET', '/fr/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        // Check response is successful
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());
        // Decode response
        $jsonResponse = json_decode($response->getContent(), true);
        // Check logged user is the one expected
        $this->assertArrayHasKey('email', $jsonResponse);
        $this->assertEquals($email, $jsonResponse['email']);
        // Check logged user has neccessary role
        $this->assertArrayHasKey("roles", $jsonResponse);
        $this->assertContains("ROLE_USER", $jsonResponse["roles"]);
        // Check logged user is not admin
        $this->assertNotContains("ROLE_ADMIN", $jsonResponse["roles"]);
    }

    /**
     * Test that admin is logged as admin.
     * @return void
     */
    public function testAdminLoginIsLoggedAsAdmin(): void
    {
        // Get admin JWT token
        $email = $this->data->getUsers()[0]["email"];
        $password = $this->data->getUsers()[0]["password"];
        $token = $this->getJwtToken($email, $password);
        // Get admin information
        $this->client->request('GET', '/fr/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        // Check response is successful
        $this->assertResponseIsSuccessful();
        // Test response is JSON format
        $this->assertJson($response->getContent());
        // Decode response
        $jsonResponse = json_decode($response->getContent(), true);
        // Check logged user is the one expected
        $this->assertArrayHasKey('email', $jsonResponse);
        $this->assertEquals($email, $jsonResponse['email']);
        // Check admin has neccessary role
        $this->assertArrayHasKey("roles", $jsonResponse);
        $this->assertContains("ROLE_ADMIN", $jsonResponse["roles"]);
    }

    /**
     * Test that bad credentials failed with code 400.
     * @return void
     */
    public function testBadCredentialsFailed(): void
    {
        // Login with bad credentials
        $this->client->request(
            'POST',
            '/fr/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'email' => "shouldNotExists",
                'password' => "",
            ])
        );
        // Assert that response is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that user creation is successful.
     * @return void
     */
    public function testUserCreationIsSuccessful(): void
    {
        // Set test email
        $email = "testCreation@test.com";
        // Create user account
        $this->client->request(
            'POST',
            '/fr/account',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'email' => $email,
                'password' => "123456",
                'username' => 'test',
                'firstname' => 'test'
            ])
        );
        // Assert that response code is created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        // Assert that user has been created in DB
        $user = $this->userRepository->findOneByEmail($email);
        $this->assertInstanceOf(User::class, $user);
        // Assert that created user has role user
        $this->assertContains("ROLE_USER", $user->getRoles());
        // Assert that created user is not admin
        $this->assertNotContains("ROLE_ADMIN", $user->getRoles());
    }

    /**
     * Test that create user with already used emails failed when used locale is french.
     * @return void
     */
    public function testFrUserCreationWithDuplicateEmailFailed(): void
    {
        // Get admin account
        $user = $this->data->getUsers()[0];

        // Try duplicating admin account
        $this->client->request(
            'POST',
            '/fr/account',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode($user)
        );

        $response = $this->client->getResponse();
        // Assert that response code is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        // Test response is JSON format
        $this->assertJson($response->getContent());
        // Decode response
        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'user.email_already_used',
                [
                    'email' => $user['email']
                ],
                'errors',
                'fr'
            ),
            $jsonResponse['error'],
        );
    }

    /**
     * Test that create user with already used emails failed when used locale is english.
     * @return void
     */
    public function testEnUserCreationWithDuplicateEmailFailed(): void
    {
        // Get admin account
        $user = $this->data->getUsers()[0];

        // Try duplicating admin account
        $this->client->request(
            'POST',
            '/en/account',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode($user)
        );

        $response = $this->client->getResponse();
        // Assert that response code is bad request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        // Test response is JSON format
        $this->assertJson($response->getContent());
        // Decode response
        $jsonResponse = json_decode($response->getContent(), true);
        // Assert that returned error message is the one expected
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertEquals(
            $this->translator->trans(
                'user.email_already_used',
                [
                    'email' => $user['email']
                ],
                'errors',
                'en'
            ),
            $jsonResponse['error'],
        );
    }
}
