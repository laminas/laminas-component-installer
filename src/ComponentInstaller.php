<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller;

use ArrayObject;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use DirectoryIterator;
use Laminas\ComponentInstaller\Injector\AbstractInjector;
use Laminas\ComponentInstaller\Injector\ConfigInjectorChain;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\PackageProvider\PackageProviderDetectionFactory;
use Laminas\ComponentInstaller\PackageProvider\PackageProviderDetectionInterface;
use RuntimeException;

use function array_diff_assoc;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unshift;
use function array_values;
use function assert;
use function explode;
use function file_exists;
use function file_get_contents;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function stripslashes;
use function strtolower;
use function substr;

/**
 * If a package represents a component module, update the application configuration.
 *
 * Packages opt-in to this workflow by defining one or more of the keys:
 *
 * - extra.laminas.component
 * - extra.laminas.module
 * - extra.laminas.config-provider
 *
 * with the value being the string namespace the component and/or module
 * defines, or, in the case of config-provider, the fully qualified class name
 * of the provider:
 *
 * <code class="lang-javascript">
 * {
 *   "extra": {
 *     "laminas": {
 *       "component": "Laminas\\Form",
 *       "module": "Laminas\\ApiTools\\ContentNegotiation",
 *       "config-provider": "Mezzio\\PlatesRenderer\\ConfigProvider"
 *     }
 *   }
 * }
 * </code>
 *
 * With regards to components and modules, for this to work correctly, the
 * package MUST define a `Module` in the namespace listed in either the
 * extra.laminas.component or extra.laminas.module definition.
 *
 * Components are added to the TOP of the modules list, to ensure that userland
 * code and/or modules can override the settings. Modules are added to the
 * BOTTOM of the modules list. Config providers are added to the TOP of
 * configuration providers.
 *
 * In either case, you can edit the appropriate configuration file when
 * complete to create a specific order.
 */
class ComponentInstaller implements
    EventSubscriberInterface,
    PluginInterface
{
    /**
     * Cached injectors to re-use for packages installed later in the current process.
     *
     * @var InjectorInterface[]
     */
    private $cachedInjectors = [];

    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    /**
     * Map of known package types to composer config keys.
     *
     * @var string[]
     */
    private $packageTypes = [
        InjectorInterface::TYPE_CONFIG_PROVIDER => 'config-provider',
        InjectorInterface::TYPE_COMPONENT       => 'component',
        InjectorInterface::TYPE_MODULE          => 'module',
    ];

    /**
     * Project root in which to install.
     *
     * @var string
     */
    private $projectRoot = '';

    /** @var Closure():PackageProviderDetectionFactory */
    private $packageProviderFactory;

    /** @var Collection|null */
    private $discovered;

    /**
     * Constructor
     *
     * Optionally accept the project root into which to install.
     *
     * @param string $projectRoot
     */
    public function __construct($projectRoot = '')
    {
        if (is_string($projectRoot) && ! empty($projectRoot) && is_dir($projectRoot)) {
            $this->projectRoot = $projectRoot;
        }
    }

    /**
     * Activate plugin.
     *
     * Sets internal pointers to Composer and IOInterface instances, and resets
     * cached injector map.
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer               = $composer;
        $this->io                     = $io;
        $this->cachedInjectors        = [];
        $this->packageProviderFactory = function (): PackageProviderDetectionFactory {
            static $factory;
            if ($factory instanceof PackageProviderDetectionFactory) {
                return $factory;
            }

            $factory = PackageProviderDetectionFactory::create($this->composer);
            return $factory;
        };
    }

    /**
     * Return list of event handlers in this class.
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL   => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE    => 'onPostPackageUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    /**
     * post-package-install event hook.
     *
     * This routine exits early if any of the following conditions apply:
     *
     * - Executed in non-development mode
     * - No config/application.config.php is available
     * - The composer.json does not define one of either extra.laminas.component
     *   or extra.laminas.module
     * - The value used for either extra.laminas.component or extra.laminas.module are
     *   empty or not strings.
     *
     * Otherwise, it will attempt to update the application configuration
     * using the value(s) discovered in extra.laminas.component and/or extra.laminas.module,
     * writing their values into the `modules` list.
     *
     * @return void
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        $operation = $event->getOperation();
        assert($operation instanceof InstallOperation);
        $package = $operation->getPackage();
        $name    = $package->getName();

        /** @var array<string,mixed> $packageExtra */
        $packageExtra = $package->getExtra();
        $extra        = $this->getExtraMetadata($packageExtra);

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $this->addPackageToConfig($name, $extra, $event, $package);
    }

    public function onPostPackageUpdate(PackageEvent $event): void
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        $operation = $event->getOperation();
        assert($operation instanceof UpdateOperation);

        $previousVersion = $operation->getInitialPackage();
        $newVersion      = $operation->getTargetPackage();
        /** @var array<string,mixed> $previousPackageExtra */
        $previousPackageExtra = $previousVersion->getExtra();
        $previousExtra        = $this->getExtraMetadata($previousPackageExtra);
        /** @var array<string,mixed> $newPackageExtra */
        $newPackageExtra = $newVersion->getExtra();
        $newExtra        = $this->getExtraMetadata($newPackageExtra);

        if ($previousExtra === $newExtra) {
            return;
        }

        // Looks like the newer version of the package does not contain component installer informations anymore
        if (empty($newExtra)) {
            $this->removePackageFromConfig($previousVersion->getName(), $previousExtra);
            return;
        }

        $removed  = array_diff_assoc($previousExtra, $newExtra);
        $appended = array_diff_assoc($newExtra, $previousExtra);

        if ($appended) {
            // Newer version of the package contains component installer informations
            $this->addPackageToConfig($newVersion->getName(), $appended, $event, $newVersion);
        }

        if ($removed) {
            $this->removePackageFromConfig($newVersion->getName(), $removed);
        }
    }

    /**
     * Find all Module classes in the package and their dependencies
     * via method `getModuleDependencies` of Module class.
     *
     * These dependencies are used later
     *
     * @see \Laminas\ComponentInstaller\Injector\AbstractInjector::injectAfterDependencies
     *      to add component in a correct order on the module list - after dependencies.
     *
     * It works with PSR-0, PSR-4, 'classmap' and 'files' composer autoloading.
     *
     * @return array
     * @psalm-return array<non-empty-string,list<non-empty-string>>
     */
    private function loadModuleClassesDependencies(PackageInterface $package)
    {
        /** @psalm-var ArrayObject<non-empty-string,list<string>> $dependencies */
        $dependencies = new ArrayObject([]);
        $installer    = $this->composer->getInstallationManager();
        $packagePath  = $installer->getInstallPath($package);

        $this->mapAutoloaders($package->getAutoload(), $dependencies, $packagePath);

        return $dependencies->getArrayCopy();
    }

    /**
     * Find all modules of the application.
     *
     * @return array
     * @psalm-return list<non-empty-string>
     */
    private function findApplicationModules()
    {
        $modulePath = is_string($this->projectRoot) && ! empty($this->projectRoot)
            ? sprintf('%s/module', $this->projectRoot)
            : 'module';

        $modules = [];

        if (is_dir($modulePath)) {
            $directoryIterator = new DirectoryIterator($modulePath);
            foreach ($directoryIterator as $file) {
                if ($file->isDot() || ! $file->isDir()) {
                    continue;
                }

                $module = $file->getBaseName();
                if (empty($module)) {
                    continue;
                }

                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * post-package-uninstall event hook
     *
     * This routine exits early if any of the following conditions apply:
     *
     * - Executed in non-development mode
     * - No config/application.config.php is available
     * - The composer.json does not define one of either extra.laminas.component
     *   or extra.laminas.module
     * - The value used for either extra.laminas.component or extra.laminas.module are
     *   empty or not strings.
     *
     * Otherwise, it will attempt to update the application configuration
     * using the value(s) discovered in extra.laminas.component and/or extra.laminas.module,
     * removing their values from the `modules` list.
     *
     * @return void
     */
    public function onPostPackageUninstall(PackageEvent $event)
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        $operation = $event->getOperation();
        assert($operation instanceof UninstallOperation);
        $package = $operation->getPackage();

        $name = $package->getName();
        /** @var array<string,mixed> $packageExtra */
        $packageExtra = $package->getExtra();
        $extra        = $this->getExtraMetadata($packageExtra);
        $this->removePackageFromConfig($name, $extra);
    }

    /**
     * Retrieve the metadata from the "extra" section
     *
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function getExtraMetadata(array $extra): array
    {
        if (isset($extra['laminas']) && is_array($extra['laminas'])) {
            /** @var array<string,mixed> $laminasSpecificConfiguration */
            $laminasSpecificConfiguration = $extra['laminas'];
            return $laminasSpecificConfiguration;
        }

        /**
         * supports legacy "extra.zf" configuration
         *
         * @var array<string,mixed> $deprecatedConfiguration
         */
        $deprecatedConfiguration = isset($extra['zf']) && is_array($extra['zf'])
            ? $extra['zf']
            : [];

        return $deprecatedConfiguration;
    }

    /**
     * Discover what package types are relevant based on what the package
     * exposes in the extra configuration.
     *
     * @param array<string,mixed> $extra
     * @return Collection Collection of Injector\InjectorInterface::TYPE_* constants.
     */
    private function discoverPackageTypes(array $extra): Collection
    {
        $packageTypes = array_flip($this->packageTypes);
        $knownTypes   = array_keys($packageTypes);

        return Collection::create($extra)
            ->filter(function ($packages, $type) use ($knownTypes) {
                return in_array($type, $knownTypes, true);
            })
            ->reduce(function (Collection $discoveredTypes, $packages, string $type) use ($packageTypes): Collection {
                $packages = is_array($packages) ? $packages : [$packages];

                foreach ($packages as $package) {
                    assert(is_string($package));
                    $discoveredTypes[$package] = $packageTypes[$type];
                }

                return $discoveredTypes;
            }, new Collection([]));
    }

    /**
     * Marshal a collection of defined package types.
     *
     * @param array $extra extra.laminas value
     * @return Collection
     */
    private function marshalPackageTypes(array $extra)
    {
        // Create a collection of types registered in the package.
        return Collection::create($this->packageTypes)
            ->filter(function ($configKey, $type) use ($extra) {
                return $this->metadataForKeyIsValid($configKey, $extra);
            });
    }

    /**
     * Marshal a collection of package modules.
     *
     * @param array      $extra   extra.laminas value
     * @param Collection $options ConfigOption instances
     * @return Collection
     */
    private function marshalPackageModules(array $extra, Collection $packageTypes, Collection $options)
    {
        // We only want to list modules that the application can configure.
        $supportedTypes = $options
            ->reduce(function ($allowed, $option) {
                return $allowed->merge($option->getInjector()->getTypesAllowed());
            }, new Collection([]))
            ->unique()
            ->toArray();

        return $packageTypes
            ->reduce(function ($modules, $configKey, $type) use ($extra, $supportedTypes) {
                if (! in_array($type, $supportedTypes, true)) {
                    return $modules;
                }

                return $modules->merge((array) $extra[$configKey]);
            }, new Collection([]))
            // Make sure the list is unique
            ->unique();
    }

    /**
     * Prepare a list of modules to install/register with configuration.
     *
     * @param string[]   $extra
     * @return Collection List of packages to install
     */
    private function marshalInstallableModules(array $extra, Collection $options)
    {
        return $this->marshalPackageModules($extra, $this->marshalPackageTypes($extra), $options)
            // Filter out modules that do not have a registered injector
            ->reject(function ($module) use ($options) {
                return $options->reduce(function ($registered, $option) use ($module) {
                    return $registered || $option->getInjector()->isRegistered($module);
                }, false);
            });
    }

    /**
     * Prompt for the user to select a configuration location to update.
     *
     * @param array $autoInstallations
     * @return InjectorInterface
     */
    private function promptForConfigOption(
        string $name,
        Collection $options,
        int $packageType,
        string $packageName,
        array $autoInstallations,
        bool $requireDev = false
    ) {
        if ($cachedInjector = $this->getCachedInjector($packageType)) {
            return $cachedInjector;
        }

        // If package is allowed to be auto-installed, don't ask...
        if (in_array($packageName, $autoInstallations, true)) {
            if ($requireDev) {
                return isset($options[2]) ? $options[2]->getInjector() : $options[1]->getInjector();
            }

            return $options[1]->getInjector();
        }

        // Default to first discovered option; index 0 is always "Do not inject"
        $default = $options->count() > 1 ? 1 : 0;
        $ask     = $options->reduce(function ($ask, $option, $index) {
            $ask[] = sprintf(
                "  [<comment>%d</comment>] %s\n",
                $index,
                $option->getPromptText()
            );

            return $ask;
        }, []);

        array_unshift($ask, sprintf(
            "\n  <question>Please select which config file you wish to inject '%s' into:</question>\n",
            $name
        ));
        $ask[] = sprintf('  Make your selection (default is <comment>%d</comment>):', $default);

        while (true) {
            /** @psalm-suppress MixedAssignment Well the method returns mixed. We do verifying this in the next lines. */
            $answer = $this->io->ask(implode($ask), $default);

            if (is_numeric($answer) && isset($options[(int) $answer])) {
                $injector = $options[(int) $answer]->getInjector();
                $this->promptToRememberOption($injector, $packageType);

                return $injector;
            }

            $this->io->write('<error>Invalid selection</error>');
        }
    }

    /**
     * Prompt the user to determine if the selection should be remembered for later packages.
     *
     * @param int                        $packageType
     * @todo Will need to store selection in filesystem and remove when all packages are complete
     */
    private function promptToRememberOption(InjectorInterface $injector, $packageType): void
    {
        $ask = ["\n  <question>Remember this option for other packages of the same type? (Y/n)</question>"];

        while (true) {
            $answer = $this->io->ask(implode($ask), 'y');
            if (! is_string($answer)) {
                throw new RuntimeException(sprintf(
                    'Expected `%s#ask` to return a string: "%s" returned',
                    IOInterface::class,
                    gettype($answer)
                ));
            }

            switch (strtolower($answer)) {
                case 'y':
                    $this->cacheInjector($injector, $packageType);

                    return;
                case 'n':
                    // intentionally fall-through
                default:
                    return;
            }
        }
    }

    /**
     * Inject a module into available configuration.
     *
     * @param string                     $package  Package name
     * @param string                     $module   Module to install in configuration
     * @param InjectorInterface $injector Injector to use.
     * @param int                        $packageType
     * @return void
     */
    private function injectModuleIntoConfig($package, $module, InjectorInterface $injector, $packageType)
    {
        $this->io->write(sprintf('<info>    Installing %s from package %s</info>', $module, $package));

        try {
            if (! $injector->inject($module, $packageType)) {
                $this->io->write('<info>    Package is already registered; skipping</info>');
            }
        } catch (Exception\RuntimeException $ex) {
            $this->io->write(sprintf(
                '<error>    %s</error>',
                $ex->getMessage()
            ));
        }
    }

    /**
     * Remove a package from configuration.
     *
     * @param string     $package       Package name
     * @param array      $metadata      Metadata pulled from extra.laminas
     * @return void
     */
    private function removePackageFromConfig($package, array $metadata)
    {
        $configOptions = $this->detectConfigurationOptions();

        if ($configOptions->isEmpty()) {
            // No configuration options found; do nothing.
            return;
        }

        // Create a collection of types registered in the package.
        $packageTypes = $this->marshalPackageTypes($metadata);

        // Create a collection of configured injectors for the package types
        // registered.
        $injectors = $configOptions
            ->map(function ($configOption) {
                return $configOption->getInjector();
            })
            ->filter(function ($injector) use ($packageTypes) {
                return $packageTypes->reduce(function ($registered, $key, $type) use ($injector) {
                    return $registered || $injector->registersType($type);
                }, false);
            });

        // Create a collection of unique modules based on the package types present,
        // and remove each from configuration.
        $this->marshalPackageModules($metadata, $packageTypes, $configOptions)
            ->each(function ($module) use ($package, $injectors) {
                $this->removeModuleFromConfig($module, $package, $injectors);
            });
    }

    /**
     * Remove an individual module defined in a package from configuration.
     *
     * @param string     $module    Module to remove
     * @param string     $package   Package in which module is defined
     * @param Collection $injectors Injectors to use for removal
     * @return void
     */
    private function removeModuleFromConfig($module, $package, Collection $injectors)
    {
        $injectors->each(function (InjectorInterface $injector) use ($module, $package) {
            if (! $injector->isRegistered($module)) {
                return;
            }

            $this->io->write(
                sprintf('<info>    Removing %s from package %s</info>', $module, $package)
            );

            if ($injector->remove($module)) {
                $this->io->write(sprintf(
                    '<info>    Removed package from %s</info>',
                    $this->getInjectorConfigFileName($injector)
                ));
            }
        });
    }

    /**
     * @return string
     * @todo remove after InjectorInterface has getConfigName defined
     */
    private function getInjectorConfigFileName(InjectorInterface $injector)
    {
        if ($injector instanceof ConfigInjectorChain) {
            return $this->getInjectorChainConfigFileName($injector);
        } elseif ($injector instanceof AbstractInjector) {
            return $this->getAbstractInjectorConfigFileName($injector);
        }

        return '';
    }

    /**
     * @return string
     * @todo remove after InjectorInterface has getConfigName defined
     */
    private function getInjectorChainConfigFileName(ConfigInjectorChain $injector)
    {
        return implode(', ', array_map(function ($item) {
            return $this->getInjectorConfigFileName($item);
        }, $injector->getCollection()->toArray()));
    }

    /**
     * @return string
     * @todo remove after InjectorInterface has getConfigName defined
     */
    private function getAbstractInjectorConfigFileName(AbstractInjector $injector)
    {
        return $injector->getConfigFile();
    }

    /**
     * Is a given module name valid?
     *
     * @param string $module
     * @return bool
     */
    private function moduleIsValid($module)
    {
        return is_string($module) && ! empty($module);
    }

    /**
     * Is a given metadata value (extra.laminas.*) valid?
     *
     * @param string $key Key to examine in metadata
     * @param array  $metadata
     * @return bool
     */
    private function metadataForKeyIsValid($key, array $metadata)
    {
        if (! isset($metadata[$key])) {
            return false;
        }

        if (is_string($metadata[$key])) {
            return $this->moduleIsValid($metadata[$key]);
        }

        if (! is_array($metadata[$key])) {
            return false;
        }

        return Collection::create($metadata[$key])
            ->reduce(function ($valid, $value) {
                if (false === $valid) {
                    return $valid;
                }

                return $this->moduleIsValid($value);
            }, null);
    }

    /**
     * Attempt to retrieve a cached injector for the current package type.
     *
     * @param int $packageType
     * @return null|InjectorInterface
     */
    private function getCachedInjector($packageType)
    {
        if (isset($this->cachedInjectors[$packageType])) {
            return $this->cachedInjectors[$packageType];
        }

        return null;
    }

    /**
     * Cache an injector for later use.
     *
     * @param int                        $packageType
     * @return void
     */
    private function cacheInjector(InjectorInterface $injector, $packageType)
    {
        $this->cachedInjectors[$packageType] = $injector;
    }

    /**
     * Iterate through each autoloader type to find dependencies.
     *
     * @param array       $autoload     List of autoloader types and associated autoloader definitions.
     * @param ArrayObject $dependencies Module dependencies defined by the module.
     * @param string      $packagePath  Path to the package on the filesystem.
     * @psalm-param array<string,array<string,string|list<string>>|list<string>> $autoload
     * @psalm-param ArrayObject<non-empty-string,list<string>>                   $dependencies
     */
    private function mapAutoloaders(array $autoload, ArrayObject $dependencies, string $packagePath): void
    {
        foreach ($autoload as $type => $map) {
            $this->mapType($map, $type, $dependencies, $packagePath);
        }
    }

    /**
     * Iterate through a single autolaoder type to find dependencies.
     *
     * @param array       $map          Map of namespace => path(s) pairs.
     * @param string      $type         Type of autoloader being iterated.
     * @param ArrayObject $dependencies Module dependencies defined by the module.
     * @param string      $packagePath  Path to the package on the filesystem.
     * @psalm-param array<string,string|list<string>>|list<string>           $map
     * @psalm-param ArrayObject<non-empty-string,list<string>>  $dependencies
     */
    private function mapType(array $map, string $type, ArrayObject $dependencies, string $packagePath): void
    {
        foreach ($map as $namespace => $paths) {
            /**
             * @psalm-suppress RedundantFunctionCallGivenDocblockType Since we only use psalm to verify v2
             *                                                        of composer here, lets keep this until dropping
             *                                                        support for composer v1.
             */
            $paths = array_values((array) $paths);
            $this->mapNamespacePaths($paths, $namespace, $type, $dependencies, $packagePath);
        }
    }

    /**
     * Iterate through the paths defined for a given namespace.
     *
     * @param array       $paths        Paths defined for the given namespace.
     * @param string|int  $namespace    PHP namespace to which the paths map or index of file/directory list.
     * @param string      $type         Type of autoloader being iterated.
     * @param ArrayObject $dependencies Module dependencies defined by the module.
     * @param string      $packagePath  Path to the package on the filesystem.
     * @psalm-param list<string> $paths
     * @psalm-param ArrayObject<non-empty-string,list<string>> $dependencies
     */
    private function mapNamespacePaths(
        array $paths,
        $namespace,
        string $type,
        ArrayObject $dependencies,
        string $packagePath
    ): void {
        foreach ($paths as $path) {
            $this->mapPath($path, $namespace, $type, $dependencies, $packagePath);
        }
    }

    /**
     * Find module dependencies for a given namespace for a given path.
     *
     * @param string      $path         Path to inspect.
     * @param string|int  $namespace    PHP namespace to which the paths map.
     * @param string      $type         Type of autoloader being iterated.
     * @param ArrayObject $dependencies Module dependencies defined by the module.
     * @param string      $packagePath  Path to the package on the filesystem.
     * @psalm-param ArrayObject<non-empty-string,list<string>> $dependencies
     */
    private function mapPath(
        string $path,
        $namespace,
        string $type,
        ArrayObject $dependencies,
        string $packagePath
    ): void {
        switch ($type) {
            case 'classmap':
                $fullPath = sprintf('%s/%s', $packagePath, $path);
                if (substr($path, -10) === 'Module.php') {
                    $modulePath = $fullPath;
                    break;
                }

                $modulePath = sprintf('%s/Module.php', rtrim($fullPath, '/'));
                break;
            case 'files':
                if (substr($path, -10) !== 'Module.php') {
                    return;
                }
                $modulePath = sprintf('%s/%s', $packagePath, $path);
                break;
            case 'psr-0':
                assert(is_string($namespace));
                $modulePath = sprintf(
                    '%s/%s%s%s',
                    $packagePath,
                    $path,
                    str_replace('\\', '/', $namespace),
                    'Module.php'
                );
                break;
            case 'psr-4':
                assert(is_string($namespace));
                $modulePath = sprintf(
                    '%s/%s%s',
                    $packagePath,
                    $path,
                    'Module.php'
                );
                break;
            default:
                return;
        }

        if (! file_exists($modulePath)) {
            return;
        }

        $result = $this->getModuleDependencies($modulePath);

        if (empty($result)) {
            return;
        }

        // Mimic array + array operation in ArrayObject
        $dependencies->exchangeArray(array_merge($dependencies->getArrayCopy(), $result));
    }

    /**
     * @psalm-return array<string,list<non-empty-string>>
     */
    private function getModuleDependencies(string $file): array
    {
        $content = file_get_contents($file);
        assert(is_string($content));
        if (preg_match('/namespace\s+([^\s]+)\s*;/', $content, $m)) {
            $moduleName = $m[1];

            // @codingStandardsIgnoreStart
            $regExp = '/public\s+function\s+getModuleDependencies\s*\(\s*\)\s*{[^}]*return\s*(?:array\(|\[)([^})\]]*)(\)|\])/';
            // @codingStandardsIgnoreEnd
            if (preg_match($regExp, $content, $m)) {
                $dependencies = array_filter(
                    explode(',', stripslashes(rtrim(preg_replace('/[\s"\']/', '', $m[1]), ',')))
                );

                if ($dependencies) {
                    /** @psalm-var list<non-empty-string> $dependencies */
                    return [$moduleName => $dependencies];
                }
            }
        }

        return [];
    }

    private function isADevDependency(
        PackageProviderDetectionInterface $packageProviderDetection,
        PackageInterface $package
    ): bool {
        $packageName     = $package->getName();
        $devRequirements = $this->composer->getPackage()->getDevRequires();
        if (isset($devRequirements[$packageName])) {
            return true;
        }

        $packages = $packageProviderDetection->whatProvides($packageName);
        if (empty($packages)) {
            return false;
        }

        $requirements = $this->composer->getPackage()->getRequires();
        foreach ($packages as $parent) {
            // Package is required by any package which is NOT a dev-requirement
            if (isset($requirements[$parent->getName()])) {
                return false;
            }
        }

        return true;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @param array<string,mixed> $extra
     */
    private function addPackageToConfig(
        string $name,
        array $extra,
        PackageEvent $event,
        PackageInterface $package
    ): void {
        $packageTypes = $this->discoverPackageTypes($extra);
        $options      = (new ConfigDiscovery())
            ->getAvailableConfigOptions($packageTypes, $this->projectRoot);

        if ($options->isEmpty()) {
            // No configuration options found; do nothing.
            return;
        }

        $packageProvider = ($this->packageProviderFactory)();

        $packageProviderDetection = $packageProvider->detect($event, $name);
        $requireDev               = $this->isADevDependency($packageProviderDetection, $package);
        $dependencies             = $this->loadModuleClassesDependencies($package);
        $applicationModules       = $this->findApplicationModules();

        $modules = $this->marshalInstallableModules($extra, $options)
            // Create injectors
            ->reduce(
                // @codingStandardsIgnoreStart
                function (Collection $injectors, string $module) use ($options, $packageTypes, $name, $requireDev): Collection {
                    // @codingStandardsIgnoreEnd
                    // Get extra from root package
                    /** @var array<string,mixed> $rootPackageExtra */
                    $rootPackageExtra  = $this->composer->getPackage()->getExtra();
                    $rootExtra         = $this->getExtraMetadata($rootPackageExtra);
                    $autoInstallations = $rootExtra['component-auto-installs']
                        ?? $rootExtra['component-whitelist']
                        ?? [];
                    assert(is_array($autoInstallations));
                    $packageType        = $packageTypes[$module];
                    $injectors[$module] = $this->promptForConfigOption(
                        $module,
                        $options,
                        $packageType,
                        $name,
                        $autoInstallations,
                        $requireDev
                    );

                    return $injectors;
                },
                new Collection([])
            );

        assert($modules instanceof Collection);

        // Inject modules into configuration
        $modules->each(function (
            InjectorInterface $injector,
            string $module
        ) use (
            $name,
            $packageTypes,
            $applicationModules,
            $dependencies
        ) {
            if (isset($dependencies[$module])) {
                $injector->setModuleDependencies($dependencies[$module]);
            }

                $injector->setApplicationModules($applicationModules);
                $this->injectModuleIntoConfig(
                    $name,
                    $module,
                    $injector,
                    $packageTypes[$module]
                );
        });
    }

    /**
     * Use internal property caching as the config options wont change while composer is being executed.
     */
    private function detectConfigurationOptions(): Collection
    {
        if ($this->discovered) {
            return $this->discovered;
        }

        $discovered = (new ConfigDiscovery())
            ->getAvailableConfigOptions(
                new Collection(array_keys($this->packageTypes)),
                $this->projectRoot
            );

        $this->discovered = $discovered;
        return $discovered;
    }
}
