<?php

namespace Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\TestData;

class TestControllerBase extends WebTestCase
{
    /**
     * Rest client.
     * @var 
     */
    protected $client;

    /**
     * Loaded test data.
     * @var TestData
     */
    protected TestData $data;

    public function setUp(): void
    {
        parent::setUp();
        // Load test client
        $this->client = static::createClient();
        // Load test data
        $this->data = new TestData();
        $this->data->loadData();
    }

    /**
     * Get JWT token.
     * @param string $email Login email.
     * @param string $password Login password.
     * @return string Generated JWT token.
     */
    protected function getJwtToken(
        string $email,
        string $password
    ): string {
        $this->client->request('POST', '/fr/token', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
                'email' => $email,
                'password' => $password,
            ]));

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $jsonResponse);

        return $jsonResponse['token'];
    }
}
