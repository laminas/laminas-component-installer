<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Exception;

/**
 * @todo add getConfigFile() method in 2.0
 */
interface InjectorInterface
{
    public const TYPE_CONFIG_PROVIDER    = 0;
    public const TYPE_COMPONENT          = 1;
    public const TYPE_MODULE             = 2;
    public const TYPE_DEPENDENCY         = 3;
    public const TYPE_BEFORE_APPLICATION = 4;

    /**
     * Whether or not the injector can handle the given type.
     *
     * @param int $type One of the TYPE_* constants.
     * @psalm-param InjectorInterface::TYPE_* $type
     * @return bool
     */
    public function registersType($type);

    /**
     * Return a list of types the injector handles.
     *
     * @return int[]
     */
    public function getTypesAllowed();

    /**
     * Is a given package already registered?
     *
     * @param string $package
     * @return bool
     */
    public function isRegistered($package);

    /**
     * Register a package with the configuration.
     *
     * @param string $package Package to inject into configuration.
     * @param int $type One of the TYPE_* constants.
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function inject($package, $type);

    /**
     * Remove a package from the configuration.
     *
     * @param string $package Package to remove.
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function remove($package);

    /**
     * Set modules of the application.
     *
     * @param array<int,string> $modules
     * @psalm-param list<non-empty-string> $modules
     * @return self
     */
    public function setApplicationModules(array $modules);

    /**
     * Set dependencies for the module.
     *
     * @param array<int,string> $modules
     * @psalm-param list<non-empty-string> $modules
     * @return self
     */
    public function setModuleDependencies(array $modules);
}
