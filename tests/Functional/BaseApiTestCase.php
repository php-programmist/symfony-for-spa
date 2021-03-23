<?php


namespace App\Tests\Functional;


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class BaseApiTestCase extends ApiTestCase
{
    use ReloadDatabaseTrait;

    protected EntityManagerInterface $entityManager;

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
}