<?php


namespace App\Tests\Functional;


class MeTest extends BaseApiTestCase
{
    public function testMe(): void
    {
        $user = $this->createUser();
        $token = $this->getToken();

        $json = $this->sendGET('/api/users/me', [
            'headers' => [
                'accept' => 'application/json'
            ],
            'auth_bearer' => $token
        ], 200);

        self::assertEquals($user->getEmail(), $json['email']);
        self::assertFalse($json['emailConfirmed']);
    }

}