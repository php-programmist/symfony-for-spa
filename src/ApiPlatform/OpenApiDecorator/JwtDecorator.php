<?php


namespace App\ApiPlatform\OpenApiDecorator;


use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ArrayObject;

final class JwtDecorator extends AbstractDecorator
{
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas();

        Schemas::addTokenSchema($schemas);
        Schemas::addCredentialsSchema($schemas);
        Schemas::addRefreshTokenSchema($schemas);

        $this->addTokenEndpoint($openApi);
        $this->addRefreshTokenEndpoint($openApi);

        return $openApi;
    }

    /**
     * @param OpenApi $openApi
     */
    private function addTokenEndpoint(OpenApi $openApi): void
    {
        $pathItem = new Model\PathItem(
            'JWT Token',
            null,
            null,
            null,
            null,
            new Model\Operation(
                'postCredentialsItem',
                [],
                [
                    '200' => [
                        'description' => 'Get JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ],
                'Get JWT token to login.',
                'Generate new JWT Token',
                null,
                [],
                new Model\RequestBody(
                    'Generate new JWT Token',
                    new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath($this->getPath('authentication_token'), $pathItem);
    }

    /**
     * @param OpenApi $openApi
     */
    private function addRefreshTokenEndpoint(OpenApi $openApi): void
    {
        $pathItem = new Model\PathItem(
            'Refresh JWT Token',
            null,
            null,
            null,
            null,
            new Model\Operation(
                'postRefreshTokenItem',
                [],
                [
                    '200' => [
                        'description' => 'Refresh JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ],
                'Refresh JWT token',
                'Generate new JWT Token by using Refresh Token',
                null,
                [],
                new Model\RequestBody(
                    'Refresh JWT token',
                    new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/RefreshCredentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath($this->getPath('gesdinet_jwt_refresh_token'), $pathItem);
    }

}