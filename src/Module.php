<?php
    /**
     * @copyright 2017 Jan-Simon Winkelmann <winkelmann@blue-metallic.de>
     * @license MIT
     */

    namespace LaminasRestQueryParamValidation;

    use LaminasRestQueryParamValidation\Factory\QueryParamValidationListenerFactory;
    use Laminas\EventManager\EventInterface;
    use Laminas\EventManager\EventManagerInterface;
    use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
    use Laminas\ModuleManager\Feature\ServiceProviderInterface;
    use Laminas\Mvc\Application;
    use Laminas\ServiceManager\ServiceLocatorInterface;

    /**
     * Module class
     */
    class Module implements BootstrapListenerInterface, ServiceProviderInterface
    {

        /**
         * @inheritdoc
         */
        public function onBootstrap(EventInterface $e)
        {

            /** @var Application $app */
            $app = $e->getTarget();

            /** @var ServiceLocatorInterface $services */
            $services = $app->getServiceManager();

            /** @var EventManagerInterface $events */
            $events = $app->getEventManager();

            $services->get(QueryParamValidationListener::class)->attachShared($events->getSharedManager());

        }

        /**
         * @inheritdoc
         */
        public function getServiceConfig()
        {

            return [
                'factories' => [
                    QueryParamValidationListener::class => QueryParamValidationListenerFactory::class
                ]
            ];

        }

    }
