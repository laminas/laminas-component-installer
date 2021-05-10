<?php

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
