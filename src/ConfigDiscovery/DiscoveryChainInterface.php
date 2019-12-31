<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

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
