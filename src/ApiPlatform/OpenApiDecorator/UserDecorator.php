<?php


namespace App\ApiPlatform\OpenApiDecorator;


use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\Routing\RouterInterface;

final class UserDecorator implements OpenApiFactoryInterface
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
     * @noinspection PhpRouteMissingInspection
     */
    private function addRegistrationEndpoint(OpenApi $openApi): void
    {
        $path = $this->router->generate('api_users_post_collection');
        $pathItem = $openApi->getPaths()->getPath($path);
        if (null === $pathItem) {
            return;
        }
        $operation = $pathItem->getPost();
        if (null === $operation) {
            return;
        }
        $operation = $operation->withResponses([
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
        $pathItem = $pathItem->withPost($operation);
        $openApi->getPaths()->addPath($path, $pathItem);
    }


    /**
     * @param OpenApi $openApi
     * @noinspection PhpRouteMissingInspection
     */
    private function addMeEndpoint(OpenApi $openApi): void
    {
        $path = $this->router->generate('api_users_me_collection');
        $pathItem = $openApi->getPaths()->getPath($path);
        if (null === $pathItem) {
            return;
        }
        $operation = $pathItem->getGet();
        if (null === $operation) {
            return;
        }
        $operation = $operation
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
        $pathItem = $pathItem->withGet($operation);

        $openApi->getPaths()->addPath($path, $pathItem);
    }


    /**
     * @param OpenApi $openApi
     */
    private function addEmailConfirmEndpoint(OpenApi $openApi): void
    {
        $route = $this->router->getRouteCollection()->get('api_users_email_confirm_item');
        if (null === $route) {
            return;
        }
        $path = $route->getPath();
        $pathItem = $openApi->getPaths()->getPath($path);
        if (null === $pathItem) {
            return;
        }
        $operation = $pathItem->getGet();
        if (null === $operation) {
            return;
        }
        $operation = $operation
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
        $pathItem = $pathItem->withGet($operation);

        $openApi->getPaths()->addPath($path, $pathItem);
    }
}