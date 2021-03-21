<?php


namespace App\ApiPlatform;


use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\Routing\RouterInterface;

final class JwtDecorator implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param OpenApiFactoryInterface $decorated
     * @param RouterInterface $router
     */
    public function __construct(
        OpenApiFactoryInterface $decorated,
        RouterInterface $router
    ) {
        $this->decorated = $decorated;
        $this->router = $router;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas();

        $this->addTokenSchema($schemas);
        $this->addCredentialsSchema($schemas);
        $this->addRefreshTokenSchema($schemas);

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
        $openApi->getPaths()->addPath($this->router->generate('authentication_token'), $pathItem);
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
        $openApi->getPaths()->addPath($this->router->generate('gesdinet_jwt_refresh_token'), $pathItem);
    }

    /**
     * @param ArrayObject|null $schemas
     */
    private function addTokenSchema(?ArrayObject $schemas): void
    {
        $schemas['Token'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);
    }

    /**
     * @param ArrayObject|null $schemas
     */
    private function addRefreshTokenSchema(?ArrayObject $schemas): void
    {
        $schemas['RefreshCredentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'refresh_token' => [
                    'type' => 'string',
                    'example' => 'xxx00a7a9e970f9bbe076e05743e00648908c38366c551a8cdf524ba424fc3e520988f6320a54989bbe85931ffe1bfcc63e33fd8b45d58564039943bfbd8dxxx',
                ],
            ],
        ]);
    }

    /**
     * @param ArrayObject|null $schemas
     */
    private function addCredentialsSchema(?ArrayObject $schemas): void
    {
        $schemas['Credentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'johndoe@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'apassword',
                ],
            ],
        ]);
    }
}