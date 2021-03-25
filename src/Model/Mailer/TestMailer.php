<?php


namespace App\Model\Mailer;


use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
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
                static fn(Address $address) => $address->getAddress() === $recipientEmail);
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
        self::$sentEmailsCounter = 0;
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
}
