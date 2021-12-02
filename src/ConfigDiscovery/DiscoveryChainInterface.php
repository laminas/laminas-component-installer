<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

interface DiscoveryChainInterface
{
    /**
     * Determine if discovery exists
     *
     * @param string $name
     * @return bool
     */
    public function discoveryExists($name);
}
