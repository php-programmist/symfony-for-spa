<?php


namespace App\Model\Email;


use App\Exception\Storage\JsonDecodeException;
use JsonSerializable;

class EmailAddress implements JsonSerializable
{
    protected const SERIALIZE_KEY_EMAIL = 'email';
    protected const SERIALIZE_KEY_TITLE = 'title';

    private string $email;
    private string $title;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            self::SERIALIZE_KEY_EMAIL => $this->getEmail(),
            self::SERIALIZE_KEY_TITLE => $this->getTitle(),
        ];
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return str_replace(['\\u00a0', '\\t', '\\r', '\\n', ' '], ['', '', '', '', ''], $this->email);
    }

    /**
     * @param string $email
     * @return EmailAddress
     */
    public function setEmail(string $email): EmailAddress
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return EmailAddress
     */
    public function setTitle(string $title): EmailAddress
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param array $data
     * @return EmailAddress
     * @throws JsonDecodeException
     */
    public function jsonDeserialize(array $data): self
    {
        if (!isset($data[self::SERIALIZE_KEY_EMAIL])) {
            throw new JsonDecodeException('Нет данных о email\' получателя');
        }
        $this->setEmail($data[self::SERIALIZE_KEY_EMAIL]);
        if (!isset($data[self::SERIALIZE_KEY_TITLE])) {
            throw new JsonDecodeException('Нет данных о наименовании получателя');
        }
        $this->setTitle($data[self::SERIALIZE_KEY_TITLE]);

        return $this;
    }
}