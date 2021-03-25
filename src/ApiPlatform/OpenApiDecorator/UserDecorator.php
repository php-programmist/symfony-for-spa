<?php


namespace App\ApiPlatform\OpenApiDecorator;

use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ArrayObject;

final class UserDecorator extends AbstractDecorator
{
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        Schemas::addTokenSchema($schemas);
        Schemas::addCredentialsSchema($schemas);
        Schemas::addViolationsSchema($schemas);
        Schemas::addSimpleStatusSchema($schemas);

        $this->addRegistrationEndpoint($openApi);
        $this->addMeEndpoint($openApi);
        $this->addEmailConfirmEndpoint($openApi);
        return $openApi;
    }

    /**
     * @param OpenApi $openApi
     */
    private function addRegistrationEndpoint(OpenApi $openApi): void
    {
        $callback = static function (Model\Operation $operation) {
            return $operation->withResponses([
                '200' => [
                    'description' => 'User resource created',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Token',
                            ],
                        ],
                    ],
                ],
                '400' => [
                    'description' => 'Invalid input',
                ],
                '422' => [
                    'description' => 'Unprocessable Entity',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Violations',
                            ],
                        ],
                    ],
                ]

            ])->withRequestBody(new Model\RequestBody(
                'Registration of new user',
                new ArrayObject([
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Credentials',
                        ],
                    ],
                ]),
            ));
        };
        $this->changeOperation($openApi, 'api_users_post_collection', self::METHOD_POST, $callback);
    }


    /**
     * @param OpenApi $openApi
     */
    private function addMeEndpoint(OpenApi $openApi): void
    {
        $callback = static function (Model\Operation $operation) {
            return $operation
                ->withSummary('Authenticated User resource.')
                ->withDescription('Retrieves authenticated User resource.')
                ->withResponses([
                    '200' => [
                        'description' => 'Authenticated User resource',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/User-user.read',
                                ],
                            ],
                            'application/ld+json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/User.jsonld-user.read',
                                ],
                            ],
                        ],
                    ],
                ]);
        };
        $this->changeOperation($openApi, 'api_users_me_collection', self::METHOD_GET, $callback);
    }


    /**
     * @param OpenApi $openApi
     */
    private function addEmailConfirmEndpoint(OpenApi $openApi): void
    {
        $callback = static function (Model\Operation $operation) {
            return $operation
                ->withSummary('Confirm user\'s email')
                ->withDescription('Confirm user\'s email.')
                ->withParameters([
                    ...$operation->getParameters(),
                    new Model\Parameter('token', 'path', 'Unique token', true)
                ])
                ->withResponses([
                    '200' => [
                        'description' => 'Email successfully confirmed',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/SimpleStatus',
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Invalid input',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Violations',
                                ],
                            ],
                        ],
                    ],
                ]);
        };

        $this->changeOperation($openApi, 'api_users_email_confirm_item', self::METHOD_GET, $callback);
    }
}