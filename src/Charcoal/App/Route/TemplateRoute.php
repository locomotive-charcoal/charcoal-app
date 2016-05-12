<?php

namespace Charcoal\App\Route;

use \InvalidArgumentException;

// Dependencies from PSR-7 (HTTP Messaging)
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Dependency from Pimple
use \Pimple\Container;

// Dependency from Slim
use \Slim\Http\Uri;

// Dependency from 'charcoal-config'
use \Charcoal\Config\ConfigurableInterface;
use \Charcoal\Config\ConfigurableTrait;

// Intra-module ('charcoal-app') dependencies
use \Charcoal\App\AppInterface;
use \Charcoal\App\Template\TemplateInterface;
use \Charcoal\App\Template\TemplateFactory;

// Local namespace dependencies
use \Charcoal\App\Route\RouteInterface;
use \Charcoal\App\Route\TemplateRouteConfig;

/**
 * Template Route Handler.
 *
 * A route handler is a simple `invokale` object with the signature:
 * `__invoke(Container $container, RequestInterface $request, ResponseInterface $response)`
 * It is only called (invoked) when a route is matched.
 *
 * This is the default "Slim Route Handler" for _template_ style routes.
 * It uses a `TemplateRouteConfig` to properly either:
 *
 * - redirect the request, if explicitely set
 * - load and render a "Template" object
 *
 * Templates can be any objects that can be loaded with a "TemplateFactory".
 * The Template Factory used is an external dependency (`template/factory`) expected to be set on the container.
 *
 * Template-level cache is possible by setting the "cache" config option to true.
 * Cached template can not have any options; they will always return the exact same content for all "template".
 *
 */
class TemplateRoute implements
    ConfigurableInterface,
    RouteInterface
{
    use ConfigurableTrait;

    /**
     * Create new template route
     *
     * **Required dependencies**
     *
     * - `config` — ScriptRouteConfig
     *
     * @param array|\ArrayInterface $data Dependencies.
     */
    public function __construct($data)
    {
        $this->setConfig($data['config']);
    }

    /**
     * ConfigurableTrait > createConfig()
     *
     * @param  mixed|null $data Optional config data.
     * @return ConfigInterface
     */
    public function createConfig($data = null)
    {
        return new TemplateRouteConfig($data);
    }

    /**
     * @param  Container         $container A DI (Pimple) container.
     * @param  RequestInterface  $request   A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response  A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function __invoke(Container $container, RequestInterface $request, ResponseInterface $response)
    {
        $config = $this->config();

        // Handle explicit redirects
        if (!empty($config['redirect'])) {
            $uri = $this->parseRedirect($config['redirect'], $request);

            if ($uri) {
                return $response->withRedirect($uri, $config['redirect_mode']);
            }
        }

        $templateIdent = $config['template'];

        if ($config['cache']) {
            $cachePool = $container['cache'];
            $cacheItem = $cachePool->getItem('template/'.$templateIdent);

            $templateContent = $cacheItem->get();
            if ($cacheItem->isMiss()) {
                $cacheItem->lock();
                $templateContent = $this->templateContent($container, $request);

                $cachePool->save($cacheItem->set($templateContent, $config['cache_ttl']));
            }
        } else {
            $templateContent = $this->templateContent($container, $request);
        }

        $response->write($templateContent);

        return $response;
    }

    /**
     * @param  Container        $container A DI (Pimple) container.
     * @param  RequestInterface $request   The request to intialize the template with.
     * @return string
     */
    protected function templateContent(Container $container, RequestInterface $request)
    {
        $config = $this->config();

        $templateIdent      = $config['template'];
        $templateController = $config['controller'];

        $templateFactory = $container['template/factory'];
        $templateFactory->setDefaultClass($config['default_controller']);

        $template = $templateFactory->create($templateController);
        $template->setDependencies($container);
        $template->init($request);

        // Set custom data from config.
        $template->setData($config['template_data']);

        return $container['view']->render($templateIdent, $template);
    }

    /**
     * @param  string           $redirection The route's destination.
     * @param  RequestInterface $request     A PSR-7 compatible Request instance.
     * @return Uri|null
     */
    protected function parseRedirect($redirection, RequestInterface $request)
    {
        $uri   = $request->getUri();
        $parts = parse_url($redirection);

        if (!empty($parts)) {
            if (isset($parts['host'])) {
                $uri = Uri::createFromString($redirection);
            } else {
                if (isset($parts['path'])) {
                    $uri = $uri->withPath($parts['path']);
                }

                if (isset($parts['query'])) {
                    $uri = $uri->withQuery($parts['query']);
                }

                if (isset($parts['fragment'])) {
                    $uri = $uri->withFragment($parts['fragment']);
                }
            }

            if ((string)$uri !== (string)$request->getUri()) {
                return $uri;
            }
        }

        return null;
    }
}
