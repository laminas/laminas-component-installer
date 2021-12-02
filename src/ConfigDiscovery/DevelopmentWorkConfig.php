<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

class DevelopmentWorkConfig extends ApplicationConfig
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/development.config.php';
}
