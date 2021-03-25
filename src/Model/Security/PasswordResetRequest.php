<?php


namespace App\Model\Security;


use App\Entity\User;
use App\Model\Uuid;
use DateTime;
use Exception;
use LogicException;
use Redis;

class PasswordResetRequest
{
    public const PASSWORD_RESET_REQUEST_TTL = 2 * 60 * 60;

    private Redis $redis;
    private string $resetToken;
    private User $user;

    /**
     * PasswordResetRequest constructor.
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
        $this->resetToken = Uuid::create();
    }

    /**
     * @return string
     */
    public function getResetToken(): string
    {
        return $this->resetToken;
    }

    /**
     * @param string $resetToken
     * @return PasswordResetRequest
     */
    public function setResetToken(string $resetToken): PasswordResetRequest
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'password:reset:' . $this->getUser()->getId();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return PasswordResetRequest
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function startRequest(): void
    {
        $this->redis->setex($this->getKey(), self::PASSWORD_RESET_REQUEST_TTL, $this->getResetToken());
    }

    public function finishRequest(): void
    {
        $this->redis->del($this->getKey());
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        $key = $this->getKey();
        $result = $this->redis->setnx($key, $this->getResetToken());
        //Даём 10 секунд на обработку сброса пароля (чтобы можно было через 10 секунд снова сбросить, если что-то пошло не так)
        if ($result) {
            $this->redis->expire($key, 10);
        } else {
            $this->loadCurrentResetToken();
        }
        return !$result;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->getResetToken() === $this->redis->get($this->getKey());
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function validUntil(): DateTime
    {
        $ttl = $this->redis->ttl($this->getKey());
        if ($ttl === false) {
            throw new LogicException('Не найден ключ для сброса пароля');
        }
        return new DateTime(time() + $ttl);
    }

    /**
     * @return bool
     */
    private function loadCurrentResetToken(): bool
    {
        $data = $this->redis->get($this->getKey());
        if (!empty($data)) {
            $this->setResetToken($data);
        }
        return !empty($data);
    }
}