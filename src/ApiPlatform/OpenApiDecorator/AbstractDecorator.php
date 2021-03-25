<?php


namespace App\ApiPlatform\OpenApiDecorator;


use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\OpenApi;
use LogicException;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractDecorator implements OpenApiFactoryInterface
{
    protected const METHOD_GET = 'Get';
    protected const METHOD_POST = 'Post';
    protected const METHOD_PUT = 'Put';
    protected const METHOD_DELETE = 'Delete';
    protected const METHOD_PATCH = 'Patch';

    protected OpenApiFactoryInterface $decorated;
    protected RouterInterface $router;

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

    abstract public function __invoke(array $context = []): OpenApi;

    /**
     * @param string $routName
     * @return string|null
     */
    protected function getPath(string $routName): ?string
    {
        $route = $this->router->getRouteCollection()->get($routName);
        if (null === $route) {
            return null;
        }
        $path = $route->getPath();
        return str_replace('.{_format}', '', $path);
    }

    /**
     * @param OpenApi $openApi
     * @param string $routName
     * @param string $method
     * @param callable $callback
     */
    protected function changeOperation(OpenApi $openApi, string $routName, string $method, callable $callback): void
    {
        $path = $this->getPath($routName);
        $pathItem = $this->getPathItem($openApi, $path);
        $operation = $this->getOperation($pathItem, $method);
        $operation = $callback($operation);
        $pathItem = $this->withOperation($pathItem, $method, $operation);
        $openApi->getPaths()->addPath($path, $pathItem);
    }

    private function getOperation(PathItem $pathItem, string $method): Operation
    {
        $methodName = sprintf('get%s', ucfirst(strtolower($method)));
        if (!method_exists($pathItem, $methodName)) {
            throw new LogicException(sprintf('Метод %s не существует в объекте класса %s', $methodName,
                get_class($pathItem)));
        }
        $operation = $pathItem->$methodName();
        if (null === $operation) {
            throw new LogicException(sprintf('Операция для метода %s не найдена', $method));
        }
        return $operation;
    }

    private function withOperation(PathItem $pathItem, string $method, Operation $operation): PathItem
    {
        $methodName = sprintf('with%s', ucfirst(strtolower($method)));
        if (!method_exists($pathItem, $methodName)) {
            throw new LogicException(sprintf('Метод %s не существует в объекте класса %s', $methodName,
                get_class($pathItem)));
        }
        return $pathItem->$methodName($operation);
    }

    private function getPathItem(OpenApi $openApi, string $path): PathItem
    {
        $pathItem = $openApi->getPaths()->getPath($path);
        if (null === $pathItem) {
            throw new LogicException(sprintf('PathItem для пути %s не найден', $path));
        }
        return $pathItem;
    }

}