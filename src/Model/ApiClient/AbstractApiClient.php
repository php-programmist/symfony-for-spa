<?php
/**
 * Created by PhpStorm.
 * User: afedorov
 * Date: 21.11.17
 * Time: 16:22
 */

namespace App\Model\ApiClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractApiClient
{
    const REQUEST_TIMEOUT = 5.0;
    const LOG_MESSAGE_FORMAT = '{method} {uri} HTTP/{version} {req_body} RESPONSE: {code} - {res_body}';

    /** @var  Client */
    protected $client;

    /** @var  string */
    protected $baseUrl;

    /** @var  string */
    protected $apiKey;

    /**
     * AbstractApiClient constructor.
     * @param string $apiKey
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $apiKey, ?LoggerInterface $logger = null)
    {
        if (empty($apiKey)) {
            throw new LogicException('Ключ API не установлен');
        }

        $this->setApiKey($apiKey);

        $params = [
            'base_uri' => $this->getBaseUrl(),
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        if (!empty($logger)) {
            $params['handler'] = $this->getLoggerHandlerStack($logger);
        }

        $this->client = new Client($params);
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param LoggerInterface $logger
     * @return HandlerStack
     */
    private function getLoggerHandlerStack(LoggerInterface $logger)
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push(
            Middleware::log(
                $logger,
                new MessageFormatter(self::LOG_MESSAGE_FORMAT)
            )
        );

        return $handlerStack;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    protected function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string| UriInterface $uri
     * @param array $options
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function sendRequest(string $uri, array $options = [], string $method = 'get')
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Параметр $options должен быть массивом');
        }

        try {
            $response = $this->getClient()->request($method, $uri, $options);
        } catch (BadResponseException $exception) {
            throw new Exception($exception->getResponse()->getBody()->getContents());
        }

        $statusCode = $response->getStatusCode();
        if ((int)($statusCode / 100) !== 2) {
            throw new LogicException("HTTP код ответа API {$statusCode}");
        }

        //Если ответ 204, то отклоняем все пришедшие к нам данные, т.к. серверы )например, в реализации AmoCRM API)
        //иногда включают некорректные данные в ответ с 204 кодом
        //https://developer.mozilla.org/ru/docs/Web/HTTP/Status/204#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%87%D0%B0%D0%BD%D0%B8%D1%8F_%D1%81%D0%BE%D0%B2%D0%BC%D0%B5%D1%81%D1%82%D0%B8%D0%BC%D0%BE%D1%81%D1%82%D0%B8
        if ($statusCode === 204) {
            $responseContents = '{}';
        } else {
            $responseContents = $this->getResponseBodyContents($response);
        }

        // Обработать результат или ошибку
        $responseData = json_decode($responseContents, true);

        if ($responseData === null) {
            throw new LogicException('Ответ API не является корректным JSON');
        }

        return $responseData;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseBodyContents(ResponseInterface $response): string
    {
        //Возвращаем указатель на начало файла
        $response->getBody()->rewind();
        return $response->getBody()->getContents();
    }
}