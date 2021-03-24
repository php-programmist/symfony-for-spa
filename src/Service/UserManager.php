<?php


namespace App\Service;


use App\Entity\User;
use App\Event\User\RegisterEvent;
use App\Event\User\UserRemovedEvent;
use App\Event\User\UserUpdatedEvent;
use App\Exception\User\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    private EntityManagerInterface $entityManager;
    private Redis $redis;
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;
    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Redis $redis
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Redis $redis,
        UserPasswordEncoderInterface $passwordEncoder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->redis = $redis;
        $this->passwordEncoder = $passwordEncoder;
        $this->dispatcher = $eventDispatcher;
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

    /**
     * @param User $user
     * @param string $password
     */
    public function setEncodedPassword(User $user, string $password): void
    {
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $user->eraseCredentials();
    }

    /**
     * @param User $user
     */
    public function registerUser(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->dispatcher->dispatch(new RegisterEvent($user));
    }

    /**
     * @param User $user
     */
    public function updateUser(User $user): void
    {
        $this->entityManager->flush();
        $this->dispatcher->dispatch(new UserUpdatedEvent($user));
    }

    /**
     * @param User $user
     */
    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(new UserRemovedEvent($user));
    }

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findOrFail(int $id): User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($id);
        if (null === $user) {
            throw new UserNotFoundException(sprintf('Не найден пользователь с ID %d', $id));
        }

        return $user;
    }
}