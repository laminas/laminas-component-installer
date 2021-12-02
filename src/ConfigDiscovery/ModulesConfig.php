<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

class ModulesConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/modules.config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * @var string
     */
    protected $expected = '/^return\s+(array\(|\[)\s*$/m';
}
