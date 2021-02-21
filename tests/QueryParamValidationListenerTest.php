<?php

declare(strict_types=1);

namespace LaminasRestQueryParamValidationTest;

use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\Rest\Resource;
use Laminas\ApiTools\Rest\ResourceEvent;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Router\Http\RouteMatch;
use Laminas\Stdlib\Parameters;
use LaminasRestQueryParamValidation\QueryParamValidationListener;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class QueryParamValidationListenerTest extends TestCase
{
    public function testAttachShared(): void
    {
        $listener = new QueryParamValidationListener(
            [],
            $this->createMock(ContainerInterface::class)
        );

        $events = $this->createMock(SharedEventManagerInterface::class);
        $events->expects($this->exactly(9))
            ->method('attach')
            ->withConsecutive(
                [Resource::class, 'create', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'delete', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'deleteList', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'fetch', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'fetchAll', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'patch', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'patchList', [$listener, 'onResourceEvent'], 10],
                [Resource::class, 'replaceList', [$listener, 'onResourceEvent'], 10],
            );

        $listener->attachShared($events);
    }

    public function testNotMatchingRoute(): void
    {
        $listener = new QueryParamValidationListener(
            [],
            $this->createMock(ContainerInterface::class)
        );
        $e = $this->createMock(ResourceEvent::class);
        $e->method('getRouteMatch')
            ->willReturn(null);

        $this->assertNull($listener->onResourceEvent($e), 'no route match should return null');
    }

    public function testNoController(): void
    {
        $listener = new QueryParamValidationListener(
            [],
            $this->createMock(ContainerInterface::class)
        );
        $e = $this->createMock(ResourceEvent::class);
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')
            ->with('controller', false)
            ->willReturn(false);
        $e->method('getRouteMatch')
            ->willReturn($routeMatch);

        $this->assertNull($listener->onResourceEvent($e), 'no controller should return null');
    }

    public function testNoInputFilter(): void
    {
        $listener = new QueryParamValidationListener(
            [],
            $this->createMock(ContainerInterface::class)
        );
        $e = $this->createMock(ResourceEvent::class);
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')
            ->with('controller', false)
            ->willReturn('controllerName');

        $e->method('getRouteMatch')
            ->willReturn($routeMatch);
        $e->method('getName')
            ->willReturn('dummy');

        $this->assertNull($listener->onResourceEvent($e), 'no input filter should return null');
    }

    public function testInputFilterNotFound(): void
    {
        $listener = new QueryParamValidationListener(
            [
                'controllerName' => [
                    'query_filter' => 'inputFilterName'
                ]
            ],
            $this->createMock(ContainerInterface::class)
        );
        $e = $this->createMock(ResourceEvent::class);
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')
            ->with('controller', false)
            ->willReturn('controllerName');

        $e->method('getRouteMatch')
            ->willReturn($routeMatch);
        $e->method('getName')
            ->willReturn('dummy');

        $response = $listener->onResourceEvent($e);
        $this->assertInstanceOf(ApiProblemResponse::class, $response, 'response is not a problem response');
        $this->assertEquals(500, $response->getStatusCode(), 'response code is incorrect');
    }

    public function testInvalidInput(): void
    {
        $inputFilterManager = $this->createMock(ContainerInterface::class);
        $inputFilterManager->method('has')
            ->willReturn(true);
        $inputFilter = $this->createMock(InputFilterInterface::class);
        $inputFilterManager->method('get')
            ->with('inputFilterName')
            ->willReturn($inputFilter);

        $listener = new QueryParamValidationListener(
            [
                'controllerName' => [
                    'query_filter' => 'inputFilterName'
                ]
            ],
            $inputFilterManager
        );
        $e = $this->createMock(ResourceEvent::class);
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')
            ->with('controller', false)
            ->willReturn('controllerName');

        $queryParams = new Parameters(
            [
                'foo' => 'bar'
            ]
        );

        $e->method('getRouteMatch')
            ->willReturn($routeMatch);
        $e->method('getName')
            ->willReturn('dummy');
        $e->method('getQueryParams')
            ->willReturn($queryParams);

        $inputFilter->expects($this->once())
            ->method('setData')
            ->with($queryParams);
        $inputFilter->method('isValid')
            ->willReturn(false);

        $response = $listener->onResourceEvent($e);
        $this->assertInstanceOf(ApiProblemResponse::class, $response, 'response is not a problem response');
        $this->assertEquals(400, $response->getStatusCode(), 'response code is incorrect');
    }

    public function testValidInput(): void
    {
        $inputFilterManager = $this->createMock(ContainerInterface::class);
        $inputFilterManager->method('has')
            ->willReturn(true);
        $inputFilter = $this->createMock(InputFilterInterface::class);
        $inputFilterManager->method('get')
            ->with('inputFilterName')
            ->willReturn($inputFilter);

        $listener = new QueryParamValidationListener(
            [
                'controllerName' => [
                    'query_filter' => 'inputFilterName'
                ]
            ],
            $inputFilterManager
        );
        $e = $this->createMock(ResourceEvent::class);
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')
            ->with('controller', false)
            ->willReturn('controllerName');

        $rawQueryParams = [
            'foo' => 'bar'
        ];
        $queryParams = new Parameters($rawQueryParams);

        $e->method('getRouteMatch')
            ->willReturn($routeMatch);
        $e->method('getName')
            ->willReturn('dummy');
        $e->method('getQueryParams')
            ->willReturn($queryParams);

        $inputFilter->expects($this->once())
            ->method('setData')
            ->with($queryParams);
        $inputFilter->method('isValid')
            ->willReturn(true);
        $inputFilter->expects($this->once())
            ->method('getValues')
            ->willReturn($rawQueryParams);

        $this->assertNull($listener->onResourceEvent($e), 'valid response should return null');
    }
}
