<?php


namespace App\Tests\Functional;


class AuthenticationTest extends BaseApiTestCase
{
    public function testLogin(): void
    {
        $user = $this->createUser();
        $token = $this->getToken();

        // test not authorized
        $this->sendGET('/users/' . $user->getId(), [], 401);

        // test authorized
        $this->sendGET('/users/' . $user->getId(), ['auth_bearer' => $token], 200);
    }
}