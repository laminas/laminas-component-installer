<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Exception;

use function addslashes;
use function count;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function reset;
use function sprintf;
use function strlen;
use function trim;

abstract class AbstractInjector implements InjectorInterface
{
    /**
     * Types this injector is allowed to register.
     *
     * Implementations MAY overwrite this value.
     *
     * @param int[]
     * @psalm-var list<InjectorInterface::TYPE_*>
     */
    protected $allowedTypes = [
        self::TYPE_COMPONENT,
        self::TYPE_MODULE,
        self::TYPE_DEPENDENCY,
        self::TYPE_BEFORE_APPLICATION,
    ];

    /**
     * Replacements to make after removing code from the configuration for clean-up purposes.
     *
     * Implementations MAY overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     'pattern' => 'regular expression',
     *     'replacement' => 'preg_replace replacement',
     * ],
     * ```
     *
     * @var array<string,string>
     * @psalm-var array{pattern: non-empty-string, replacement: string}
     */
    protected $cleanUpPatterns = [
        'pattern'     => "/(array\(|\[|,)(\r?\n){2}/s",
        'replacement' => "\$1\n",
    ];

    /**
     * Configuration file to update.
     *
     * Implementations MUST overwrite this value.
     *
     * @var string
     * @psalm-param non-empty-string
     */
    protected $configFile = 'to-be-overridden';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * Implementations MUST overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     TYPE_CONSTANT => [
     *         'pattern' => 'regular expression',
     *         'replacement' => 'preg_replace replacement, with %s placeholder for package',
     *     ],
     * ]
     * ```
     *
     * @var array<int,array<string,string>>
     * @psalm-var array<
     *     InjectorInterface::TYPE_*,
     *     array{pattern: non-empty-string, replacement: string}
     * >
     */
    protected $injectionPatterns = [];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Implementations MUST overwrite this value.
     *
     * @var string
     * @psalm-var non-empty-string
     */
    protected $isRegisteredPattern = 'to-be-overridden';

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * Implementations MUST overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     'pattern' => 'regular expression, with %s placeholder for component namespace/configuration class',
     *     'replacement' => 'preg_replace replacement, usually an empty string',
     * ],
     * ```
     *
     * @var array<string,string>
     * @psalm-var array{pattern: non-empty-string, replacement: string}
     */
    protected $removalPatterns = [
        'pattern'     => 'to-be-overridden',
        'replacement' => '',
    ];

    /**
     * Modules of the application.
     *
     * @var array<int,string>
     * @psalm-var list<non-empty-string>
     */
    protected $applicationModules = [];

    /**
     * Dependencies of the module.
     *
     * @var array<int,string>
     * @psalm-var list<non-empty-string>
     */
    protected $moduleDependencies = [];

    /**
     * Constructor
     *
     * Optionally accept the project root directory; if non-empty, it is used
     * to prefix the $configFile.
     *
     * @param string $projectRoot
     */
    public function __construct($projectRoot = '')
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_string($projectRoot) && trim($projectRoot) !== '') {
            $this->configFile = sprintf('%s/%s', $projectRoot, $this->configFile);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registersType($type)
    {
        return in_array($type, $this->allowedTypes, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesAllowed()
    {
        return $this->allowedTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered($package)
    {
        $config = file_get_contents($this->configFile);
        return $this->isRegisteredInConfig($package, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type)
    {
        $config = file_get_contents($this->configFile);

        if ($this->isRegisteredInConfig($package, $config)) {
            return false;
        }

        if (
            $type === self::TYPE_COMPONENT
            && $this->moduleDependencies
        ) {
            return $this->injectAfterDependencies($package, $config);
        }

        if (
            $type === self::TYPE_MODULE
            && ($firstApplicationModule = $this->findFirstEnabledApplicationModule($this->applicationModules, $config))
        ) {
            return $this->injectBeforeApplicationModules($package, $config, $firstApplicationModule);
        }

        $pattern     = $this->injectionPatterns[$type]['pattern'];
        $replacement = sprintf(
            $this->injectionPatterns[$type]['replacement'],
            $package
        );

        $config = preg_replace($pattern, $replacement, $config, 1);
        file_put_contents($this->configFile, $config);

        return true;
    }

    /**
     * Injects component $package into $config after all other dependencies.
     *
     * If any dependencies are not registered, the method throws
     * Exception\RuntimeException.
     *
     * @param string $package
     * @param string $config
     * @return true
     * @throws Exception\RuntimeException
     */
    private function injectAfterDependencies($package, $config)
    {
        foreach ($this->moduleDependencies as $dependency) {
            if (! $this->isRegisteredInConfig($dependency, $config)) {
                throw new Exception\RuntimeException(sprintf(
                    'Dependency %s is not registered in the configuration',
                    $dependency
                ));
            }
        }

        $lastDependency = $this->findLastDependency($this->moduleDependencies, $config);

        $pattern     = sprintf(
            $this->injectionPatterns[self::TYPE_DEPENDENCY]['pattern'],
            preg_quote($lastDependency, '/')
        );
        $replacement = sprintf(
            $this->injectionPatterns[self::TYPE_DEPENDENCY]['replacement'],
            $package
        );

        $config = preg_replace($pattern, $replacement, $config, 1);
        file_put_contents($this->configFile, $config);

        return true;
    }

    /**
     * Find which of dependency packages is the last one on the module list.
     *
     * @param array $dependencies
     * @param string $config
     * @return string
     */
    private function findLastDependency(array $dependencies, $config)
    {
        if (count($dependencies) === 1) {
            return reset($dependencies);
        }

        $longLength = 0;
        $last       = null;
        foreach ($dependencies as $dependency) {
            preg_match(sprintf($this->isRegisteredPattern, preg_quote($dependency, '/')), $config, $matches);

            $length = strlen($matches[0]);
            if ($length > $longLength) {
                $longLength = $length;
                $last       = $dependency;
            }
        }

        return $last;
    }

    /**
     * Inject module $package into $config before the first found application module
     * and return true.
     * If there is no any enabled application module, this method will return false.
     *
     * @param string $package
     * @param string $config
     * @param string $firstApplicationModule
     * @return bool
     */
    private function injectBeforeApplicationModules($package, $config, $firstApplicationModule)
    {
        $pattern     = sprintf(
            $this->injectionPatterns[self::TYPE_BEFORE_APPLICATION]['pattern'],
            preg_quote($firstApplicationModule, '/')
        );
        $replacement = sprintf(
            $this->injectionPatterns[self::TYPE_BEFORE_APPLICATION]['replacement'],
            $package
        );

        $config = preg_replace($pattern, $replacement, $config, 1);
        file_put_contents($this->configFile, $config);

        return true;
    }

    /**
     * Find the first enabled application module from list $modules in the $config.
     * If any module is not found method will return null.
     *
     * @param array $modules
     * @param string $config
     * @return string|null
     */
    private function findFirstEnabledApplicationModule(array $modules, $config)
    {
        $shortest = strlen($config);
        $first    = null;
        foreach ($modules as $module) {
            if (! $this->isRegistered($module)) {
                continue;
            }

            preg_match(sprintf($this->isRegisteredPattern, preg_quote($module, '/')), $config, $matches);

            $length = strlen($matches[0]);
            if ($length < $shortest) {
                $shortest = $length;
                $first    = $module;
            }
        }

        return $first;
    }

    /**
     * {@inheritDoc}
     */
    public function setApplicationModules(array $modules)
    {
        $this->applicationModules = $modules;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setModuleDependencies(array $modules)
    {
        $this->moduleDependencies = $modules;

        return $this;
    }

    /**
     * Removes a package from the configuration.
     * Returns true if successfully removed,
     * false when package is not registered.
     *
     * @param string $package Package name.
     * @return bool
     */
    public function remove($package)
    {
        $config = file_get_contents($this->configFile);

        if (! $this->isRegisteredInConfig($package, $config)) {
            return false;
        }

        $config = preg_replace(
            sprintf($this->removalPatterns['pattern'], preg_quote($package)),
            $this->removalPatterns['replacement'],
            $config
        );

        $config = preg_replace(
            $this->cleanUpPatterns['pattern'],
            $this->cleanUpPatterns['replacement'],
            $config
        );

        file_put_contents($this->configFile, $config);

        return true;
    }

    /**
     * Returns config file name of the injector.
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Is the code item registered in the configuration already?
     *
     * @param string $package Package name
     * @param string $config
     * @return bool
     */
    protected function isRegisteredInConfig($package, $config)
    {
        return preg_match(sprintf($this->isRegisteredPattern, preg_quote($package, '/')), $config)
            || preg_match(sprintf($this->isRegisteredPattern, preg_quote(addslashes($package), '/')), $config);
    }
}
