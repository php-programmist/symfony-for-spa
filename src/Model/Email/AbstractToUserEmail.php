<?php


namespace App\Model\Email;


use App\Entity\User;
use App\Exception\Storage\JsonDecodeException;
use App\Exception\User\UserNotFoundException;
use App\Service\EmailManager;
use Exception;

abstract class AbstractToUserEmail extends AbstractEmail
{
    protected const SERIALIZER_KEY_USER_ID = 'user_id';

    protected ?int $userId = null;
    protected User $user;

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                self::SERIALIZER_KEY_USER_ID => $this->getUserId(),
            ]
        );
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $id
     * @return AbstractToUserEmail
     */
    public function setUserId(?int $id): self
    {
        $this->userId = $id;
        if (null !== $this->userId) {
            $this->metadata['user_id'] = $this->getUserId();
        }
        return $this;
    }

    /**
     * @param array $data
     * @return AbstractEmail
     * @throws JsonDecodeException
     */
    public function jsonDeserialize(array $data): AbstractEmail
    {
        parent::jsonDeserialize($data);

        $this->setUserId($data[self::SERIALIZER_KEY_USER_ID]);

        return $this;
    }

    /**
     * @param User $user
     * @param bool $isRecipient
     * @return AbstractToUserEmail
     */
    public function setUser(User $user, bool $isRecipient = true): self
    {
        $this->user = $user;
        $this->setUserId($user->getId());
        if ($isRecipient) {
            $this->setRecipient($user->getEmailAddress());
        }
        return $this;
    }

    /**
     * @param EmailManager $manager
     * @return array
     * @throws UserNotFoundException
     * @throws Exception
     */
    protected function getPlaceholders(EmailManager $manager): array
    {
        return array_merge(
            parent::getPlaceholders($manager),
            $this->getUser($manager)->getEmailSubstitutions()
        );
    }

    /**
     * @param EmailManager|null $manager
     * @return User
     * @throws UserNotFoundException
     */
    public function getUser(EmailManager $manager = null): User
    {
        if (empty($this->user) && $manager !== null) {
            $this->user = $manager->getUser($this->getUserId());
        }
        return $this->user;
    }
}