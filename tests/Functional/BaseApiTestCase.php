<?php


namespace App\Tests\Functional;


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class BaseApiTestCase extends ApiTestCase
{
    use ReloadDatabaseTrait;

    public const TEST_USER_EMAIL = 'test@example.com';
    public const TEST_USER_PASSWORD = '123456';

    protected EntityManagerInterface $entityManager;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        $this->entityManager ??= self::$container->get('doctrine')->getManager();
        return $this->entityManager;
    }

    public function persist(object $object): void
    {
        $this->getEntityManager()->persist($object);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function persistAndFlush(object $object): void
    {
        $this->persist($object);
        $this->flush();
    }

    public function sendRequest(string $method, string $url, array $options = [], ?int $expectedCode = null): array
    {
        try {
            $response = $this->client->request($method, $url, $options);
            if (null !== $expectedCode) {
                self::assertResponseStatusCodeSame($expectedCode);
            }
            return $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }

    }

    public function sendPOST(string $url, array $options = [], ?int $expectedCode = null): array
    {
        return $this->sendRequest('POST', $url, $options, $expectedCode);
    }

    public function sendGET(string $url, array $options = [], ?int $expectedCode = null): array
    {
        return $this->sendRequest('GET', $url, $options, $expectedCode);
    }

    public function sendPUT(string $url, array $options = [], ?int $expectedCode = null): array
    {
        return $this->sendRequest('PUT', $url, $options, $expectedCode);
    }

    public function sendDELETE(string $url, array $options = [], ?int $expectedCode = null): array
    {
        return $this->sendRequest('DELETE', $url, $options, $expectedCode);
    }

    public function createUser(
        string $email = self::TEST_USER_EMAIL,
        string $password = self::TEST_USER_PASSWORD,
        array $roles = []
    ): User {
        $user = (new User())
            ->setEmail($email)
            ->setRoles($roles);

        $user->setPassword(
            self::$container->get('security.password_encoder')->encodePassword($user, $password)
        );

        $this->persistAndFlush($user);

        return $user;
    }

    /**
     * @param string $email
     * @param string $password
     * @return string
     */
    public function getToken(
        string $email = self::TEST_USER_EMAIL,
        string $password = self::TEST_USER_PASSWORD
    ): string {
        $json = $this->sendPOST('/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ], 200);

        self::assertArrayHasKey('token', $json);
        return $json['token'];
    }
}