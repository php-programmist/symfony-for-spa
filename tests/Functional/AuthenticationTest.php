<?php


namespace App\Tests\Functional;


use App\Entity\User;

class AuthenticationTest extends BaseApiTestCase
{
    public function testLogin(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(
            self::$container->get('security.password_encoder')->encodePassword($user, '$3CR3T')
        );

        $this->persistAndFlush($user);

        // retrieve a token
        $json = $this->sendPOST('/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ], 200);

        self::assertArrayHasKey('token', $json);

        // test not authorized
        $this->sendGET('/api/users/' . $user->getId(), [], 401);

        // test authorized
        $this->sendGET('/api/users/' . $user->getId(), ['auth_bearer' => $json['token']], 200);
    }
}