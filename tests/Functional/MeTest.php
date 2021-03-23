<?php


namespace App\Tests\Functional;


use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MeTest extends BaseApiTestCase
{
    /**
     * @throws TransportExceptionInterface
     */
    public function testMe(): void
    {
        $user = $this->createUser();
        $token = $this->getToken();

        // test authorized
        $this->client->request('GET', '/api/me', ['auth_bearer' => $token]);
        self::assertResponseRedirects('/api/users/' . $user->getId());
    }

}