<?php

namespace Charcoal\App\ServiceProvider;

// Dependencies from Pimple
use \Pimple\ServiceProviderInterface;
use \Pimple\Container;

// Intra-module (`charcoal-app`) dependencies
use \Charcoal\App\Action\ActionFactory;
use \Charcoal\App\Action\ActionInterface;
use \Charcoal\App\Route\RouteFactory;
use \Charcoal\App\Script\ScriptFactory;
use \Charcoal\App\Script\ScriptInterface;

use \Charcoal\App\Handler\Error;
use \Charcoal\App\Handler\PhpError;
use \Charcoal\App\Handler\Shutdown;
use \Charcoal\App\Handler\NotAllowed;
use \Charcoal\App\Handler\NotFound;

use \Charcoal\App\Template\TemplateFactory;
use \Charcoal\App\Template\TemplateInterface;
use \Charcoal\App\Template\TemplateBuilder;
use \Charcoal\App\Template\WidgetFactory;
use \Charcoal\App\Template\WidgetInterface;
use \Charcoal\App\Template\WidgetBuilder;

/**
 * Application Service Provider
 *
 * Configures Charcoal and Slim and provides various Charcoal services to a container.
 *
 * ## Services
 * - `logger` `\Psr\Log\Logger`
 *
 * ## Helpers
 * - `logger/config` `\Charcoal\App\Config\LoggerConfig`
 *
 * ## Requirements / Dependencies
 * - `config` A `ConfigInterface` must have been previously registered on the container.
 */
class AppServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance.
     * @return void
     */
    public function register(Container $container)
    {
        $this->registerHandlerServices($container);
        $this->registerRouteServices($container);
        $this->registerRequestControllerServices($container);
    }

    /**
     * @param Container $container The DI container.
     * @return void
     */
    protected function registerHandlerServices(Container $container)
    {
        $config = $container['config'];

        if (!isset($config['handlers'])) {
            return;
        }

        /**
         * HTTP 404 (Not Found) handler.
         *
         * @param  object|HandlerInterface $handler   An error handler instance.
         * @param  Container               $container A container instance.
         * @return HandlerInterface
         */
        $container->extend('notFoundHandler', function ($handler, Container $container) use ($config) {
            if ($handler instanceof \Slim\Handlers\NotFound) {
                $handler = new NotFound($container);

                if (isset($config['handlers']['notFound'])) {
                    $handler->config()->merge($config['handlers']['notFound']);
                }

                $handler->init();
            }

            return $handler;
        });

        /**
         * HTTP 405 (Not Allowed) handler.
         *
         * @param  object|HandlerInterface $handler   An error handler instance.
         * @param  Container               $container A container instance.
         * @return HandlerInterface
         */
        $container->extend('notAllowedHandler', function ($handler, Container $container) use ($config) {
            if ($handler instanceof \Slim\Handlers\NotAllowed) {
                $handler = new NotAllowed($container);

                if (isset($config['handlers']['notAllowed'])) {
                    $handler->config()->merge($config['handlers']['notAllowed']);
                }

                $handler->init();
            }

            return $handler;
        });

        /**
         * HTTP 500 (Error) handler for PHP 7+ Throwables.
         *
         * @param  object|HandlerInterface $handler   An error handler instance.
         * @param  Container               $container A container instance.
         * @return HandlerInterface
         */
        $container->extend('phpErrorHandler', function ($handler, Container $container) use ($config) {
            if ($handler instanceof \Slim\Handlers\PhpError) {
                $handler = new PhpError($container);

                if (isset($config['handlers']['phpError'])) {
                    $handler->config()->merge($config['handlers']['phpError']);
                }

                $handler->init();
            }

            return $handler;
        });

        /**
         * HTTP 500 (Error) handler.
         *
         * @param  object|HandlerInterface $handler   An error handler instance.
         * @param  Container               $container A container instance.
         * @return HandlerInterface
         */
        $container->extend('errorHandler', function ($handler, Container $container) use ($config) {
            if ($handler instanceof \Slim\Handlers\Error) {
                $handler = new Error($container);

                if (isset($config['handlers']['error'])) {
                    $handler->config()->merge($config['handlers']['error']);
                }

                $handler->init();
            }

            return $handler;
        });

        if (!isset($container['shutdownHandler'])) {
            /**
             * HTTP 503 (Service Unavailable) handler.
             *
             * This handler is not part of Slim.
             *
             * @param  Container $container A container instance.
             * @return HandlerInterface
             */
            $container['shutdownHandler'] = function (Container $container) {
                $config  = $container['config'];
                $handler = new Shutdown($container);

                if (isset($config['handlers']['shutdown'])) {
                    $handler->config()->merge($config['handlers']['shutdown']);
                }

                return $handler->init();
            };
        }
    }

    /**
     * @param Container $container The DI container.
     * @return void
     */
    protected function registerRouteServices(Container $container)
    {
        /**
         * @param Container $container A container instance.
         * @return RouteFactory
         */
        $container['route/factory'] = function (Container $container) {
            $routeFactory = new RouteFactory();
            $routeFactory->setArguments([
                'logger' => $container['logger']
            ]);
            return $routeFactory;
        };
    }

    /**
     * @param Container $container The DI container.
     * @return void
     */
    protected function registerRequestControllerServices(Container $container)
    {
        /**
         * @param Container $container A container instance.
         * @return ActionFactory
         */
        $container['action/factory'] = function (Container $container) {
            $actionFactory = new ActionFactory();
            $actionFactory->setArguments([
                'logger' => $container['logger']
            ]);
            $actionFactory->setCallback(function(ActionInterface $obj) use ($container) {
                $obj->setDependencies($container);
            });
            return $actionFactory;
        };

        /**
         * @param Container $container A container instance.
         * @return ScriptFactory
         */
        $container['script/factory'] = function (Container $container) {
            $scriptFactory = new ScriptFactory();
            $scriptFactory->setArguments([
                'logger' => $container['logger']
            ]);
            $scriptFactory->setCallback(function(ScriptInterface $obj) use ($container) {
                $obj->setDependencies($container);
            });
            return $scriptFactory;
        };

        /**
         * @param Container $container A container instance.
         * @return TemplateFactory
         */
        $container['template/factory'] = function (Container $container) {
            $templateFactory = new TemplateFactory();
            $templateFactory->setArguments([
                'logger' => $container['logger']
            ]);
            $templateFactory->setCallback(function(TemplateInterface $obj) use ($container) {
                $obj->setDependencies($container);
            });
            return $templateFactory;
        };

        /**
         * @param Container $container A container instance.
         * @return TemplateBuilder
         */
        $container['template/builder'] = function (Container $container) {
            return new TemplateBuilder($container['template/factory'], $container);
        };

        /**
         * @param Container $container A container instance.
         * @return WidgetFactory
         */
        $container['widget/factory'] = function (Container $container) {
            $widgetFactory = new WidgetFactory();
            $widgetFactory->setArguments([
                'logger' => $container['logger']
            ]);
            $widgetFactory->setCallback(function(WidgetInterface $obj) use ($container) {
                $obj->setDependencies($container);
            });
            return $widgetFactory;
        };
        /**
         * @param Container $container A container instance.
         * @return TemplateBuilder
         */
        $container['widget/builder'] = function (Container $container) {
            return new WidgetBuilder($container['widget/factory'], $container);
        };
    }
}
