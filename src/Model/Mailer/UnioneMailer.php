<?php


namespace App\Model\Mailer;


use App\Model\ApiClient\UniOneApiClient;
use App\Model\Email\EmailAddress;
use Exception;
use LogicException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class UnioneMailer implements MailerInterface
{
    /**
     * @var UniOneApiClient
     */
    private UniOneApiClient $apiClient;

    /**
     * SwiftMailer constructor.
     * @param UniOneApiClient $apiClient
     */
    public function __construct(UniOneApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param RawMessage|MailerEmail $email
     * @param Envelope|null $envelope
     * @return void
     * @throws Exception
     */
    public function send(RawMessage $email, Envelope $envelope = null): void
    {
        $this->apiClient->emailSend($this->getEmailData($email));
    }

    /**
     * @param MailerEmail $email
     * @return array
     */
    private function getEmailData(MailerEmail $email): array
    {
        $senders = $email->getFrom();
        if (empty($senders)) {
            throw new LogicException('В письме не указаны отправители');
        }

        /** @var EmailAddress $sender */
        $sender = $senders[0];

        return [
            'body' => [
                'html' => $email->getHtmlBody(),
            ],
            'subject' => $email->getSubject(),
            'from_email' => $sender->getEmail(),
            'from_name' => $sender->getTitle(),
            'recipients' => [
                ['email' => $email->getTo()[0]->getAddress()]
            ],
            'template_engine' => 'velocity',
            'global_substitutions' => $this->getSubstitutions($email->getPlaceholders()),
            'attachments' => $this->adaptAttachments($email),
            'skip_unsubscribe' => true,
            'metadata' => $email->getMetadata(),
        ];
    }

    /**
     * @param array $getPlaceholders
     * @return array
     */
    private function getSubstitutions(array $getPlaceholders): array
    {
        $result = [];
        foreach ($getPlaceholders as $key => $data) {
            $key = substr($key, 1);
            $parts = explode('.', $key);
            if (count($parts) === 1) {
                $result[$key] = $data;
            } elseif (count($parts) === 2) {
                $result[$parts[0]] = array_merge($result[$parts[0]] ?? [], [$parts[1] => $data]);
            } else {
                throw new LogicException('Количество вложений в placeholder\'ах письма превышает 2');
            }
        }

        return $result;
    }

    private function adaptAttachments(Email $email): array
    {
        return array_map(static fn($item) => [
            'name' => $item['name'],
            'type' => $item['contentType'],
            'content' => $item['body'],
        ], $email->getAttachments());
    }
}
