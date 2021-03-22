<?php


namespace App\Service;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Redis;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserManager
{
    private EntityManagerInterface $entityManager;
    private Redis $redis;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Redis $redis
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
            $this->redis->setex($this->getLoginBlockKey($email), $delay, $user->getFailedLoginCount());
        }
    }

    public function getLoginBlockKey(string $email): string
    {
        return 'login:block:' . $email;
    }

    /**
     * @param string $requestBody
     * @return Response|null
     * @throws JsonException
     */
    public function getLoginBlockResponse(string $requestBody): ?Response
    {
        $response = null;
        $data = json_decode($requestBody, true, 512, JSON_THROW_ON_ERROR);
        $email = $data['email'];
        $code = Response::HTTP_BAD_REQUEST;

        $key = $this->getLoginBlockKey($email);
        $failedCount = $this->redis->get($key);
        if ($failedCount > 0) {
            $ttl = $this->redis->ttl($key);
            $response = new JsonResponse([
                'code' => $code,
                'message' => sprintf('Аккаунт заблокирован на %d секунд из-за %d последовательных неудачных попыток входа в систему',
                    $ttl, $failedCount),
            ], $code);
        }

        return $response;
    }
}