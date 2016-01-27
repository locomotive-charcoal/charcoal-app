<?php

namespace Charcoal\App\Config;

// Module `charcoal-config` dependencies
use \Charcoal\Config\AbstractConfig;

// Monolog Dependencies
use \Monolog\Logger;

/**
 *
 */
class LoggerConfig extends AbstractConfig
{
    /**
     * @var boolean $active
     */
    private $active;

    /**
     * @var array $handlers
     */
    private $handlers;

    /**
     * @var array $processors
     */
    private $processors;

    /**
     * @return array
     */
    public function defaults()
    {
        return [
            'active' => true,
            'handlers' => [
                'stream' => [
                    'type'      => 'stream',
                    'stream'    => 'charcoal.app.log',
                    'level'     => 'debug',
                    'bubble'    => true,
                    'active'    => true
                ],
                'console' => [
                    'type'      => 'browser-console',
                    'level'     => 'debug',
                    'active'    => false
                ]
            ],
            'processors' => [
                [
                    'type' => 'memory-usage'
                ],
                [
                    'type' => 'uid'
                ]
            ]
        ];
    }

    /**
     * @param boolean $active The active flag.
     * @return LoggerConfig Chainable
     */
    public function setActive($active)
    {
        $this->active = !!$active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function active()
    {
        return $this->active;
    }

    /**
     * @param array $handlers The (monolog) logger handlers.
     * @return LoggerConfig Chainable
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;
        return $this;
    }

    /**
     * @return array
     */
    public function handlers()
    {
        return $this->handlers;
    }

    /**
     * @param array $processors The (monolog) logger processors.
     * @return LoggerConfig Chainable
     */
    public function setProcessors(array $processors)
    {
        $this->processors = $processors;
        return $this;
    }

    /**
     * @return array
     */
    public function processors()
    {
        return $this->processors;
    }
}