<?php

namespace Laminas\ComponentInstaller\ConfigDiscovery;

class DevelopmentConfig extends ApplicationConfig
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/development.config.php.dist';
}
