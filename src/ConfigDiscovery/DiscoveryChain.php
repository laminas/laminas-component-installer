<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\Collection;

class DiscoveryChain implements DiscoveryInterface, DiscoveryChainInterface
{
    /**
     * Discovery Collection
     *
     * @var Collection
     */
    protected $chain;

    /**
     * Constructor
     *
     * Optionally specify project directory; $configFile will be relative to
     * this value.
     *
     * @param mixed  $discovery
     * @param string $projectDirectory
     */
    public function __construct($discovery, $projectDirectory = '')
    {
        $this->chain = Collection::create($discovery)
            // Create a discovery class for the dicovery type
            ->map(function ($discoveryClass) use ($projectDirectory) {
                return new $discoveryClass($projectDirectory);
            })
            // Use only those where we can locate a corresponding config file
            ->filter(function ($discovery) {
                return $discovery->locate();
            });
    }

    /**
     * {@inheritDoc}
     */
    public function locate()
    {
        return $this->chain->count() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function discoveryExists($name)
    {
        return $this->chain->offsetExists($name);
    }
}
