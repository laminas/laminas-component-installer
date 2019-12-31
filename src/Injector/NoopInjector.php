<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

class NoopInjector implements InjectorInterface
{
    /**
     * {@inheritDoc}
     *
     * @return true
     */
    public function registersType($type)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesAllowed()
    {
        return [];
    }

    /**
     * @param string $package
     * @return false
     */
    public function isRegistered($package)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($package)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setApplicationModules(array $modules)
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setModuleDependencies(array $modules)
    {
        return $this;
    }
}
