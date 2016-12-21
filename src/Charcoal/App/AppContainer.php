<?php

namespace Charcoal\App;

// Dependency from Slim
use \Slim\Container;

// Dependency from Pimple
use \Pimple\ServiceProviderInterface;

// Depedencies from `charcoal-factory`
use \Charcoal\Factory\GenericFactory as Factory;

/**
 * Charcoal App Container
 */
class AppContainer extends Container
{
    /**
     * Create new container
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = [])
    {
        // Initialize container for Slim and Pimple
        parent::__construct($values);

        // Ensure "config" is set
        $this['config'] = (isset($values['config']) ? $values['config'] : []);

        $this->registerProviderFactory();
        $this->registerConfigProviders();

    }

    /**
     * @return void
     */
    private function registerProviderFactory()
    {
        if (!isset($this['provider/factory'])) {
            $this['provider/factory'] = function (Container $container) {
                return new Factory([
                    'base_class'       => ServiceProviderInterface::class,
                    'resolver_options' => [
                        'suffix' => 'ServiceProvider'
                    ]
                ]);
            };
        }
    }

    /**
     * @return void
     */
    private function registerConfigProviders()
    {
        $defaultProviders = [
            'charcoal/app/service-provider/app'        => [],
            'charcoal/app/service-provider/cache'      => [],
            'charcoal/app/service-provider/database'   => [],
            'charcoal/app/service-provider/logger'     => [],
            'charcoal/app/service-provider/translator' => [],
            'charcoal/app/service-provider/view'       => [],
        ];

        if (!empty($this['config']['service_providers'])) {
            $providers = array_replace($defaultProviders, $this['config']['service_providers']);
        } else {
            $providers = $defaultProviders;
        }

        foreach ($providers as $provider => $values) {
            if (false === $values) {
                continue;
            }

            if (!is_array($values)) {
                $values = [];
            }

            $provider = $this['provider/factory']->get($provider);

            $this->register($provider, $values);
        }
    }
}
