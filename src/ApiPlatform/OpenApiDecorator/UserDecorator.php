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
        Schemas::addEmailSchema($schemas);
        Schemas::addPasswordSchema($schemas);
        Schemas::addProblemSchema($schemas);

        $this->addRegistrationEndpoint($openApi);
        $this->addMeEndpoint($openApi);
        $this->addEmailConfirmEndpoint($openApi);
        $this->addPasswordResetRequestEndpoint($openApi);
        $this->addPasswordResetConfirmEndpoint($openApi);
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
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Violations',
                            ],
                        ],
                    ],
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
                    new Model\Parameter('token', 'path', 'Unique token from email', true)
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

    /**
     * @param OpenApi $openApi
     */
    private function addPasswordResetRequestEndpoint(OpenApi $openApi): void
    {
        $callback = static function (Model\Operation $operation) {
            return $operation
                ->withSummary('Request for password reset')
                ->withDescription('Request for password reset')
                ->withResponses([
                    '200' => [
                        'description' => 'Password reset requested successfully',
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
                ])->withRequestBody(new Model\RequestBody(
                    'Email for password reset request',
                    new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Email',
                            ],
                        ],
                    ]),
                ));
        };

        $this->changeOperation($openApi, 'api_users_password_reset_request_collection', self::METHOD_POST, $callback);
    }

    /**
     * @param OpenApi $openApi
     */
    private function addPasswordResetConfirmEndpoint(OpenApi $openApi): void
    {
        $callback = static function (Model\Operation $operation) {
            return $operation
                ->withSummary('Confirmation of password reset')
                ->withDescription('Type your new password')
                ->withParameters([
                    ...$operation->getParameters(),
                    new Model\Parameter('token', 'path', 'Unique token from email', true)
                ])
                ->withResponses([
                    '200' => [
                        'description' => 'Password changed successfully',
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
                    '404' => [
                        'description' => 'Token or user not found',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Problem',
                                ],
                            ],
                        ],
                    ],
                ])->withRequestBody(new Model\RequestBody(
                    'Type your new password and confirm it',
                    new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/PasswordWithConfirmation',
                            ],
                        ],
                    ]),
                ));
        };

        $this->changeOperation($openApi, 'api_users_password_reset_confirm_item', self::METHOD_POST, $callback);
    }
}