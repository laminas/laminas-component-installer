<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

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
