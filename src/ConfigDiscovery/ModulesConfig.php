<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

/**
 * @internal
 */
final class ModulesConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     */
    protected string $configFile = 'config/modules.config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     */
    protected string $expected = '/^return\s+(array\(|\[)\s*$/m';
}
