<?php

namespace App\Model\ApiClient;


use Exception;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

class UniOneApiClient extends AbstractApiClient
{
    /**
     * {@inheritDoc}
     */
    protected $baseUrl = 'https://eu1.unione.io/ru/transactional/api/v1/';

    /** @var  string */
    protected $userName;

    /**
     * UniOneApiClient constructor.
     * @param string $apiKey
     * @param string $userName
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $apiKey, string $userName, ?LoggerInterface $logger)
    {
        if (empty($userName)) {
            throw new LogicException('Имя пользователя API UniOne не установлено');
        }

        parent::__construct($apiKey, $logger);
        $this->setUserName($userName);
    }

    /**
     * Установить webhook с указанным URL и параметрами, по умолчанию для всех событий.
     * @param string $url
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function webhookSet(string $url, array $options = []): array
    {
        if (empty($url)) {
            throw new LogicException('Параметр $url должен быть корректным URL');
        }

        $json = [
            'url' => $url,
            'event_format' => 'json_post',
            'delivery_info' => 1,
            'single_event' => 1,
            'max_parallel' => 100,
            'events' => [
                'email_status' => [
                    'sent',
                    'delivered',
                    'opened',
                    'hard_bounced',
                    'soft_bounced',
                    'spam',
                    'clicked',
                    'unsubscribed'
                ],
                'spam_block' => [
                    '*'
                ],
            ],
        ];

        $json = array_merge($json, $options);

        $options = [
            RequestOptions::JSON => $json,
        ];

        return $this->sendRequest('webhook/set.json', $options, 'post');
    }

    /**
     * @param string $uri
     * @param array $options
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function sendRequest(string $uri, array $options = [], string $method = 'get'): array
    {
        $options[RequestOptions::JSON] = array_merge(
            $this->getDefaultJson(),
            $options[RequestOptions::JSON] ?? []
        );

        return parent::sendRequest($uri, $options, $method);
    }

    /**
     * @return array
     */
    protected function getDefaultJson(): array
    {
        return [
            'api_key' => $this->getApiKey(),
            'username' => $this->getUserName(),
        ];
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    protected function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * Получить параметры webhook для указанного URL
     * @param string $url
     * @return array
     * @throws Exception
     */
    public function webhookGet(string $url): array
    {
        if (empty($url)) {
            throw new LogicException('Параметр $url должен быть корректным URL');
        }

        $options = [
            RequestOptions::JSON => [
                'url' => $url,
            ],
        ];

        return $this->sendRequest('webhook/get.json', $options, 'post');
    }

    /**
     * Удалить webhook по указанному URL
     * @param string $url
     * @return array
     * @throws Exception
     */
    public function webhookDelete(string $url): array
    {
        if (empty($url)) {
            throw new LogicException('Параметр $url должен быть корректным URL');
        }

        $options = [
            RequestOptions::JSON => [
                'url' => $url,
            ],
        ];

        return $this->sendRequest('webhook/delete.json', $options, 'post');
    }

    /**
     * Отправить письмо
     * @param array $message
     * @return array
     * @throws Exception
     */
    public function emailSend(array $message): array
    {
        if (empty($message['template_id']) && empty($message['body'])) {
            throw new InvalidArgumentException('Необходимо указать либо идентификатор шаблона либо тело сообщения');
        }

        $options = [
            RequestOptions::JSON => [
                'message' => $message,
            ],
        ];

        return $this->sendRequest('email/send.json', $options, 'post');
    }
}
