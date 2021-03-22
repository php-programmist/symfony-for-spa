<?php


namespace App\Service;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Redis;

class UserManager
{
    private EntityManagerInterface $entityManager;
    private Redis $redis;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, Redis $redis)
    {
        $this->entityManager = $entityManager;
        $this->redis = $redis;
    }

    public function setAuthenticationSuccess(User $user): void
    {
        $user->setAuthenticationSuccessData();
        $this->entityManager->flush();
    }

    public function setAuthenticationFailure(string $email): void
    {
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findByEmail($email);
        if (null !== $user) {
            $user->setAuthenticationFailureData();
            $this->entityManager->flush();
        }

        $delay = $user->getLoginBlockDelay();
        if ($delay > 0) {
            $this->redis->setex($this->getLoginBlockKey($email), $delay, true);
        }
    }

    public function getLoginBlockKey(string $email): string
    {
        return 'login:block:' . $email;
    }
}