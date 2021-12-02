<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller;

use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;
use Laminas\ComponentInstaller\Injector\InjectorInterface;

use function assert;
use function class_exists;
use function is_array;
use function is_string;

class ConfigDiscovery
{
    /**
     * Map of known configuration files and their locators.
     *
     * @var string[]
     * @psalm-var array<string,class-string<DiscoveryInterface>|array<string,class-string<DiscoveryInterface>>>
     */
    private $discovery = [
        'config/application.config.php'      => ConfigDiscovery\ApplicationConfig::class,
        'config/modules.config.php'          => ConfigDiscovery\ModulesConfig::class,
        'config/development.config.php.dist' => [
            'dist' => ConfigDiscovery\DevelopmentConfig::class,
            'work' => ConfigDiscovery\DevelopmentWorkConfig::class,
        ],
        'config/config.php'                  => [
            'aggregator' => ConfigDiscovery\ConfigAggregator::class,
            'manager'    => ConfigDiscovery\MezzioConfig::class,
        ],
    ];

    /**
     * Map of config files to injectors
     *
     * @var string[]
     * @psalm-var array<string,class-string<InjectorInterface>|array<string,class-string<InjectorInterface>>>
     */
    private $injectors = [
        'config/application.config.php'      => Injector\ApplicationConfigInjector::class,
        'config/modules.config.php'          => Injector\ModulesConfigInjector::class,
        'config/development.config.php.dist' => [
            'dist' => Injector\DevelopmentConfigInjector::class,
            'work' => Injector\DevelopmentWorkConfigInjector::class,
        ],
        'config/config.php'                  => [
            'aggregator' => Injector\ConfigAggregatorInjector::class,
            'manager'    => Injector\MezzioConfigInjector::class,
        ],
    ];

    /**
     * Return a list of available configuration options.
     *
     * @param Collection $availableTypes Collection of Injector\InjectorInterface::TYPE_*
     *     constants indicating valid package types that could be injected.
     * @param string $projectRoot Path to the project root; assumes PWD by
     *     default.
     * @return Collection Collection of ConfigOption instances.
     */
    public function getAvailableConfigOptions(Collection $availableTypes, $projectRoot = '')
    {
        // Create an initial collection to which we'll append.
        // This approach is used to ensure indexes are sane.
        $discovered = new Collection([
            new ConfigOption('Do not inject', new Injector\NoopInjector()),
        ]);

        Collection::create($this->discovery)
            // Create a discovery class for the discovery type
            ->map(function ($discoveryClass) use ($projectRoot): DiscoveryInterface {
                if (is_array($discoveryClass)) {
                    return new ConfigDiscovery\DiscoveryChain($discoveryClass, $projectRoot);
                }
                assert(is_string($discoveryClass) && class_exists($discoveryClass));
                $discovery = new $discoveryClass($projectRoot);
                assert($discovery instanceof DiscoveryInterface);
                return $discovery;
            })
            // Use only those where we can locate a corresponding config file
            ->filter(function ($discovery) {
                return $discovery->locate();
            })
            // Create an injector for the config file
            ->map(function ($discovery, $file) use ($projectRoot, $availableTypes) {
                // Look up the injector based on the file type
                $injectorClass = $this->injectors[$file];
                if (is_array($injectorClass)) {
                    /** @psalm-suppress MixedArgument */
                    return new Injector\ConfigInjectorChain(
                        $injectorClass,
                        $discovery,
                        $availableTypes,
                        $projectRoot
                    );
                }
                return new $injectorClass($projectRoot);
            })
            // Keep only those injectors that match types available for the package
            ->filter(function ($injector) use ($availableTypes) {
                return $availableTypes->reduce(function ($flag, $type) use ($injector) {
                    return $flag || $injector->registersType($type);
                }, false);
            })
            // Create a config option using the file and injector
            ->each(function ($injector, $file) use ($discovered) {
                $discovered[] = new ConfigOption($file, $injector);
            });

        return 1 === $discovered->count()
            ? new Collection([])
            : $discovered;
    }
}
