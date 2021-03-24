<?php


namespace App\Model\Mailer;


use App\Model\Email\EmailAddress;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

class TestMailer implements MailerInterface
{
    private static array $sentEmails = [];
    private static bool $isCatchEmails = false;
    private static int $sentEmailsCounter = 0;

    /**
     * @param RawMessage|MailerEmail $email
     * @param Envelope|null $envelope
     * @return void
     */
    public function send(RawMessage $email, Envelope $envelope = null): void
    {
        if (self::$isCatchEmails) {
            $email->html(
                $this->applySubstitutions($email->getHtmlBody(), $email->getPlaceholders())
            );
            self::$sentEmails[] = $email;
        }
        self::$sentEmailsCounter++;
    }

    /**
     * @param string $recipientEmail
     * @return array|MailerEmail[]
     */
    public static function getSentEmailsTo(string $recipientEmail): array
    {
        return array_filter(self::getSentEmails(), static function (MailerEmail $email) use ($recipientEmail) {
            $matched = array_filter($email->getTo(),
                static fn(EmailAddress $address) => $address->getEmail() === $recipientEmail);
            return count($matched) > 0;
        });
    }

    /**
     * @return array|MailerEmail[]
     */
    public static function getSentEmails(): array
    {
        return self::$sentEmails;
    }

    public static function clear(): void
    {
        self::$sentEmails = [];
    }

    public static function startCatch(): void
    {
        self::clear();
        self::$isCatchEmails = true;
    }

    public static function stopCatch(): void
    {
        self::$isCatchEmails = false;
    }

    /**
     * @return int
     */
    public static function getSentEmailsCount(): int
    {
        return self::$sentEmailsCounter;
    }

    /**
     * @param string $data
     * @param array $placeholders
     * @return string
     */
    private function applySubstitutions(string $data, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $data);
    }
}
