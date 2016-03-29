<?php

namespace Charcoal\App\Template;

// PSR-3 (logger) dependencies
use \Psr\Log\LoggerAwareInterface;
use \Psr\Log\LoggerAwareTrait;

// Dependencies from `Pimple`
use \Pimple\Container;

// Module `charcoal-config` dependencies
use \Charcoal\Config\AbstractEntity;

// Intra-module (`charcoal-app`) dependencies
use \Charcoal\App\Template\TemplateInterface;

/**
 * Template (View Controller) base class
 */
abstract class AbstractTemplate extends AbstractEntity implements
    LoggerAwareInterface,
    TemplateInterface
{
    use LoggerAwareTrait;

    /**
     * @param array|\ArrayAccess $data The dependencies (app and logger).
     */
    public function __construct($data = null)
    {
        $this->setLogger($data['logger']);
    }

    /**
     * Give an opportunity to children classes to inject dependencies from a Pimple Container.
     *
     * Does nothing by default, reimplement in children classes.
     *
     * The `$container` DI-container (from `Pimple`) should not be saved or passed around, only to be used to
     * inject dependencies (typically via setters).
     *
     * @param Container $container A dependencies container instance.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        // This method is a stub. Reimplement in children template classes.
    }
}
