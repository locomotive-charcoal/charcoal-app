<?php

namespace Charcoal\App;

/**
 *
 */
interface AppInterface
{
    /**
     * Retrieve the application's module manager.
     *
     * @return ModuleManager
     */
    public function module_manager();

    /**
     * Retrieve the application's route manager.
     *
     * @return RouteManager
     */
    public function route_manager();

    /**
     * Retrieve the application's middleware manager.
     *
     * @return MiddlewareManager
     */
    public function middleware_manager();

    /**
     * Retrieve the application's language manager.
     *
     * @return LanguageManager
     */
    public function language_manager();

    /**
     * Run application
     *
     * @param  boolean $silent If TRUE, will run in silent mode (no response).
     * @return AppInterface Chainable
     */
    public function run($silent = false);
}
