<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\ConfigDiscovery;

interface DiscoveryInterface
{
    /**
     * Attempt to discover if a given configuration file exists.
     *
     * Implementations should check if the file exists, and can potentially
     * look for known expected artifacts within the file to determine if
     * the configuration is one to which the installer can or should write to.
     *
     * @return bool
     */
    public function locate();
}
