<?php


namespace App\Model\MessageBus\Message;


use App\Exception\Storage\JsonDecodeException;
use App\Model\Email\AbstractEmail;
use App\Model\Email\EmailFactory;
use App\Model\PersistInterface;
use App\Model\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use JsonSerializable;

abstract class AbstractSendEmailMessage implements PersistInterface, JsonSerializable
{
    public const STORAGE_PREFIX = 'email';
    public const STORAGE_TTL = 24 * 60 * 60;

    protected const STORAGE_DATA_KEY_EMAIL = 'email';

    /**
     * @var AbstractEmail|null
     */
    private ?AbstractEmail $email;

    /**
     * @var string
     */
    private string $uuid;

    /**
     * @var string|null
     */
    private ?string $recipientEmail = null;

    /**
     * SendEmailMessage constructor.
     * @param AbstractEmail|null $email
     */
    public function __construct(?AbstractEmail $email = null)
    {
        $this->uuid = Uuid::create();
        $this->email = $email;
        if ($email !== null) {
            $this->recipientEmail = $email->getRecipient()->getEmail();
        }
    }

    /**
     * @return array
     */
    public function getStorageData(): array
    {
        return [
            self::STORAGE_DATA_KEY_EMAIL => $this->email,
        ];
    }

    /**
     * @param array $data
     * @param EntityManagerInterface $entityManager
     * @throws JsonDecodeException
     */
    public function setStorageData(array $data, EntityManagerInterface $entityManager)
    {
        $this->email = EmailFactory::createFromJsonData($data[self::STORAGE_DATA_KEY_EMAIL]);
    }

    /**
     * @return int
     */
    public function getStorageTtl(): int
    {
        return self::STORAGE_TTL;
    }

    /**
     * @return AbstractEmail|null
     */
    public function getEmail(): ?AbstractEmail
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getStorageKey(): string
    {
        return self::STORAGE_PREFIX . '::' . $this->uuid;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'recipientEmail' => $this->recipientEmail,
        ];
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return SendEmailMessage
     */
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }
}