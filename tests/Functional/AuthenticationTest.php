<?php


namespace App\Tests\Functional;


use App\Entity\User;

class AuthenticationTest extends BaseApiTestCase
{
    public function testLogin(): void
    {
        $client = self::createClient();

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(
            self::$container->get('security.password_encoder')->encodePassword($user, '$3CR3T')
        );

        $this->persistAndFlush($user);

        // retrieve a token
        $response = $client->request('POST', '/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $json = $response->toArray();
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $json);

        // test not authorized
        $client->request('GET', '/api/users/' . $user->getId());
        self::assertResponseStatusCodeSame(401);

        // test authorized
        $client->request('GET', '/api/users/' . $user->getId(), ['auth_bearer' => $json['token']]);
        self::assertResponseIsSuccessful();
    }
}