<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

class ApplicationConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/application.config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * @var string
     */
    protected $expected = '/^(\s+)(\'modules\'\s*\=\>\s*(array\(|\[))\s*$/m';
}
