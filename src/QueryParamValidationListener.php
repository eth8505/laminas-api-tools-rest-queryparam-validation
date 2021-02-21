<?php
/**
 * @copyright Jan-Simon Winkelmann <winkelmann@blue-metallic.de>
 * @license MIT
 */

namespace LaminasRestQueryParamValidation;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\Rest\Resource;
use Laminas\ApiTools\Rest\ResourceEvent;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Router\Http\RouteMatch;
use Psr\Container\ContainerInterface;

class QueryParamValidationListener extends AbstractListenerAggregate
{
    protected array $config = [];
    protected ContainerInterface $inputFilterManager;

    /**
     * @var callable[]
     */
    protected array $sharedListeners = [];
    protected array $inputFilters = [];

    public function __construct(array $config = [], ContainerInterface $inputFilterManager)
    {
        $this->config = $config;
        $this->inputFilterManager = $inputFilterManager;
    }

    /**
     * @inheritdoc
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('fetchAll', [$this, 'onResourceEvent'], 10);
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the SharedEventManager
     * implementation will pass this to the aggregate.
     *
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events): void
    {
        $this->sharedListeners[] = $events->attach(Resource::class, 'create', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'delete', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'deleteList', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'fetch', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'fetchAll', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'patch', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'patchList', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'replaceList', [$this, 'onResourceEvent'], 10);
        $this->sharedListeners[] = $events->attach(Resource::class, 'update', [$this, 'onResourceEvent'], 10);
    }

    /**
     * Detach all previously attached listeners
     *
     * @param SharedEventManagerInterface $events
     */
    public function detachShared(SharedEventManagerInterface $events): void
    {
        foreach ($this->sharedListeners as $index => $listener) {
            if ($events->detach(Resource::class, $listener)) {
                unset($this->sharedListeners[$index]);
            }
        }
    }

    /**
     * @param ResourceEvent $e
     * @return ApiProblemResponse|null
     */
    public function onResourceEvent(ResourceEvent $e): ?ApiProblemResponse
    {
        $routeMatches = $e->getRouteMatch();
        if (!$routeMatches instanceof RouteMatch) {
            return null;
        }

        $controllerService = $routeMatches->getParam('controller', false);
        if (!$controllerService) {
            return null;
        }

        $inputFilterService = $this->getInputFilterServiceName($controllerService, $e->getName());
        if (!$inputFilterService) {
            return null;
        }

        if (!$this->hasInputFilter($inputFilterService)) {
            return new ApiProblemResponse(
                new ApiProblem(
                    500,
                    sprintf('Listed input filter "%s" does not exist; cannot validate request', $inputFilterService)
                )
            );
        }

        $inputFilter = $this->getInputFilter($inputFilterService);

        $inputFilter->setData($e->getQueryParams());
        if ($inputFilter->isValid()) {
            $e->getQueryParams()->fromArray($inputFilter->getValues());

            return null;
        }

        return new ApiProblemResponse(
            new ApiProblem(
                400,
                'Failed Validation',
                null,
                null,
                [
                    'validation_messages' => $inputFilter->getMessages(),
                ]
            )
        );
    }

    protected function getInputFilterServiceName(string $controllerService, string $resourceEventName): ?string
    {
        if (!empty($this->config[$controllerService]['query_filter'])) {
            /** @var string|array|null $inputFilter */
            $inputFilter = $this->config[$controllerService]['query_filter'];

            if (is_array($inputFilter)) {
                if (isset($inputFilter[$resourceEventName])) {
                    return $inputFilter[$resourceEventName];
                } elseif (isset($inputFilter['default'])) {
                    return $inputFilter['default'];
                }
            } else {
                return $inputFilter;
            }
        }

        return null;
    }

    protected function hasInputFilter(string $inputFilterService): bool
    {
        if (array_key_exists($inputFilterService, $this->inputFilters)) {
            return true;
        }
        if (!$this->inputFilterManager
            || !$this->inputFilterManager->has($inputFilterService)
        ) {
            return false;
        }
        $inputFilter = $this->inputFilterManager->get($inputFilterService);
        if (!$inputFilter instanceof InputFilterInterface) {
            return false;
        }
        $this->inputFilters[$inputFilterService] = $inputFilter;

        return true;
    }

    protected function getInputFilter(string $inputFilterService): InputFilterInterface
    {
        return $this->inputFilters[$inputFilterService];
    }
}
