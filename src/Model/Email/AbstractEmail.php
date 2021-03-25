<?php


namespace App\Model\Email;


use App\Exception\Storage\JsonDecodeException;
use App\Helper\DateTimeHelper;
use App\Model\Mailer\MailerEmail;
use App\Model\Uuid;
use App\Service\EmailManager;
use DateTimeInterface;
use Exception;
use JsonSerializable;

abstract class AbstractEmail implements JsonSerializable
{
    public const SERIALIZE_KEY_ALIAS = 'alias';

    protected string $uuid;
    protected ?EmailAddress $recipient;
    protected array $attachments = [];
    protected ?DateTimeInterface $sendAt = null;
    protected array $templateParams = [];
    protected ?string $subject = null;
    protected array $substitutions = [];
    protected array $metadata = [];
    protected bool $highPriority = false;

    /**
     * Email constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::create();
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    abstract public function getAlias(): string;


    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'alias' => $this->getAlias(),
            'recipient' => $this->getRecipient(),
            'attachments' => $this->getAttachments(),
            'substitutions' => $this->getSubstitutions(),
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * @return array
     */
    public function getSubstitutions(): array
    {
        return $this->substitutions;
    }

    /**
     * @param array $substitutions
     * @return CustomUserEmail
     */
    public function setSubstitutions(array $substitutions): self
    {
        $this->substitutions = $substitutions;

        return $this;
    }

    /**
     * @param array $data
     * @return AbstractEmail
     * @throws JsonDecodeException
     */
    public function jsonDeserialize(array $data): self
    {
        $this->uuid = $data['uuid'];
        $this->recipient = (new EmailAddress())->jsonDeserialize($data['recipient']);
        $this->attachments = $data['attachments'] ?? [];
        $this->substitutions = $data['substitutions'] ?? [];
        $this->metadata = $data['metadata'] ?? [];

        return $this;
    }

    /**
     * @param EmailManager $manager
     * @return MailerEmail
     */
    public function getMailerEmail(EmailManager $manager): MailerEmail
    {
        $html = $manager->render(
            $this->getTemplate(),
            $this->getTemplateParams(),
            $this->getPlaceholders($manager)
        );
        return (new MailerEmail())
            ->from($manager->getSenderAddress()->getAddress())
            ->setPlaceholders($this->getPlaceholders($manager))
            ->to($this->getRecipient()->getAddress())
            ->setAttachments($this->getAttachments())
            ->setMetadata($this->getMetadata())
            ->subject($this->getSubject())
            ->html($html);
    }

    /**
     * @param EmailManager $manager
     * @return array
     */
    protected function getPlaceholders(EmailManager $manager): array
    {
        return array_merge($manager->getBasePlaceholders(), $this->getSubstitutions());
    }

    /**
     * @return EmailAddress|null
     */
    public function getRecipient(): ?EmailAddress
    {
        return $this->recipient;
    }

    /**
     * @param EmailAddress $recipient
     * @return AbstractEmail
     */
    public function setRecipient(EmailAddress $recipient): self
    {
        $this->recipient = $recipient;
        $this->metadata['email'] = $recipient->getEmail();

        return $this;
    }

    /**
     * @return string
     */
    abstract protected function getSubject(): string;

    /**
     * @return string
     */
    abstract protected function getTemplate(): ?string;

    /**
     * @return array of arrays - [
     *                  'body' => 'content of file',
     *                  'name' => 'displayed name of file',
     *                  'contentType' => 'mime type'
     *              ]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param array $attachments - array of arrays - [
     *                  'body' => 'content of file',
     *                  'name' => 'displayed name of file',
     *                  'contentType' => 'mime type'
     *              ]
     * @return AbstractEmail
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getSendAt(): ?DateTimeInterface
    {
        return $this->sendAt;
    }

    /**
     * @param DateTimeInterface $sendAt
     * @return AbstractEmail
     */
    public function setSendAt(DateTimeInterface $sendAt): self
    {
        $this->sendAt = $sendAt;
        return $this;
    }

    /**
     * @param string $interval - example: '+1 hour' or 'tomorrow'
     * @param string $timeZone - example: 'Europe/Moscow'
     * @param bool $businessHours - if true, date will be in Business Hours interval
     * @return AbstractEmail
     * @throws Exception
     */
    public function setSendAtByInterval(string $interval, string $timeZone, bool $businessHours = false): self
    {
        $this->sendAt = DateTimeHelper::getDateByInterval($interval, $timeZone, $businessHours);
        return $this;
    }

    /**
     * @param int $hour
     * @param string $timeZone
     * @return AbstractEmail
     * @throws Exception
     */
    public function setSendAtHour(int $hour, string $timeZone): self
    {
        $this->sendAt = DateTimeHelper::getNextHour($hour, $timeZone);
        return $this;
    }

    /**
     * @return int delay in milliseconds
     */
    public function getDelay(): int
    {
        $delay = DateTimeHelper::getDiffInSeconds($this->sendAt);
        if ($delay < 0) {
            return 0;
        }
        return $delay * 1000;
    }

    /**
     * @return array
     */
    public function getTemplateParams(): array
    {
        return $this->templateParams;
    }

    /**
     * @param array|null $templateParams
     * @return $this
     */
    public function setTemplateParams(?array $templateParams): self
    {
        if (null !== $templateParams) {
            $this->templateParams = $templateParams;
        }
        return $this;
    }

    /**
     * @param array $metadata
     * @return CustomUserEmail
     */
    public function addMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     * @return AbstractEmail
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHighPriority(): bool
    {
        return $this->highPriority;
    }

    /**
     * @param bool $highPriority
     * @return $this
     */
    public function setHighPriority(bool $highPriority): self
    {
        $this->highPriority = $highPriority;
        return $this;
    }
}
