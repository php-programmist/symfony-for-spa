<?php


namespace App\Model\Mailer;


use App\Helper\PatternHelper;
use Symfony\Component\Mime\Email;

class MailerEmail extends Email
{
    protected array $placeholders = [];
    protected array $metadata = [];

    /**
     * @return string
     */
    public function getHtmlBody(): string
    {
        return PatternHelper::linksHrefPositionChange(parent::getHtmlBody());
    }

    /**
     * @return array
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     * @param array $placeholders
     * @return MailerEmail
     */
    public function setPlaceholders(array $placeholders): self
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    /**
     * @param array $attachments - array of arrays - [
     *                  'body' => 'content of file',
     *                  'name' => 'displayed name of file',
     *                  'contentType' => 'mime type'
     *              ]
     * @return MailerEmail
     */
    public function setAttachments(array $attachments): self
    {
        foreach ($attachments as $attachment) {
            $this->attach($attachment['body'], $attachment['name'], $attachment['contentType']);
        }

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
     * @return MailerEmail
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
