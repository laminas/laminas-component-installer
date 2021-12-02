<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use function str_replace;

trait ConditionalDiscoveryTrait
{
    /**
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     *
     * @param string $package Package to inject into configuration.
     * @param int $type One of the TYPE_* constants.
     * @return bool
     */
    public function inject($package, $type)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return false;
        }

        return parent::inject('\\' . $package, $type);
    }

    /**
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     *
     * @param string $package Package to remove.
     * @return bool
     */
    public function remove($package)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return false;
        }

        return parent::remove('\\' . $package);
    }

    /**
     * Does the config file hold valid ConfigAggregator configuration?
     *
     * @return bool
     */
    private function validConfigAggregatorConfig()
    {
        $discoveryClass = $this->discoveryClass;
        $discovery      = new $discoveryClass($this->getProjectRoot());
        return $discovery->locate();
    }

    /**
     * Calculate the project root from the config file
     *
     * @return string
     */
    private function getProjectRoot()
    {
        $configFile = $this->configFile;
        if (static::DEFAULT_CONFIG_FILE === $configFile) {
            return '';
        }

        return str_replace('/' . static::DEFAULT_CONFIG_FILE, '', $configFile);
    }
}
