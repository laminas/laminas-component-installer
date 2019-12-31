<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

use Composer\IO\IOInterface;

trait ConditionalDiscoveryTrait
{
    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function inject($package, $type, IOInterface $io)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return;
        }

        parent::inject('\\' . $package, $type, $io);
    }

    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function remove($package, IOInterface $io)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return;
        }

        parent::remove('\\' . $package, $io);
    }

    /**
     * Does the config file hold valid ConfigAggregator configuration?
     *
     * @return bool
     */
    private function validConfigAggregatorConfig()
    {
        $discoveryClass = $this->discoveryClass;
        $discovery = new $discoveryClass($this->getProjectRoot());
        return $discovery->locate();
    }

    /**
     * Calculate the project root from the config file
     *
     * @return string
     */
    private function getProjectRoot()
    {
        if (static::DEFAULT_CONFIG_FILE === $this->configFile) {
            return '';
        }
        return str_replace('/' . static::DEFAULT_CONFIG_FILE, '', $this->configFile);
    }
}
