<?php

declare(strict_types=1);

namespace Camelot\Api\Authentication\Tests\Functional;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Camelot\Api\Authentication\Tests\Fixtures\Entity\User;

/**
 * @internal
 * @coversNothing
 */
final class AuthenticationTest extends ApiTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = $this->client->getContainer();

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($container->get('security.user_password_hasher')->hashPassword($user, '$3CR3T'));

        $manager = $container->get('doctrine')->getManager();
        $manager->persist($user);
        $manager->flush();

        parent::setUp();
    }

    public function testLogin(): void
    {
        static::assertArrayHasKey('token', $this->login());
    }

    public function testAuthorizedAccess(): void
    {
        $json = $this->login();

        $this->client->request('GET', '/api/users', ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();
    }

    public function testMissingBearerTokenFailsAccess(): void
    {
        $this->login();
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefresh(): void
    {
        ['refresh_token' => $token] = $this->login();

        $response = $this->client->request('POST', '/api/auth/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'refresh_token' => $token,
            ],
        ]);

        $json = $response->toArray();

        $this->assertResponseIsSuccessful();

        static::assertArrayHasKey('token', $json);
    }

    private function login(): array
    {
        $response = $this->client->request('POST', '/api/auth/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $json = $response->toArray();

        $this->assertResponseIsSuccessful();

        return $json;
    }
}
