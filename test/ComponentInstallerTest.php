<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Pool;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\RootPackageRepository;
use Generator;
use Laminas\ComponentInstaller\ComponentInstaller;
use Laminas\ComponentInstaller\PackageProvider\PackageProviderDetectionFactory;
use LaminasTest\ComponentInstaller\TestAsset\NativeTypehintedInstallationManager as InstallationManager;
use LaminasTest\ComponentInstaller\TestAsset\NativeTypehintedPackageInterface as PackageInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

use function count;
use function dirname;
use function file_get_contents;
use function implode;
use function is_string;
use function method_exists;
use function mkdir;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strpos;

/**
 * @psalm-type ComponentInstallerConfiguration array{component?:string,module?:string}
 * @psalm-type ComposerExtraLaminasConfiguration array{laminas?:ComponentInstallerConfiguration}
 */
final class ComponentInstallerTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $projectRoot;

    /** @var ComponentInstaller */
    private $installer;

    /** @var Composer&MockObject */
    private $composer;

    /** @var RootPackage&MockObject */
    private $rootPackage;

    /** @var IOInterface&MockObject */
    private $io;

    /** @var InstallationManager&MockObject */
    private $installationManager;

    /** @var array{laminas?:array{component-whitelist?:list<non-empty-string>}} */
    private $rootPackageExtra = [];

    protected function setUp(): void
    {
        $this->projectRoot = vfsStream::setup('project');
        $this->installer   = new ComponentInstaller(
            vfsStream::url('project')
        );

        $composer       = $this->createMock(Composer::class);
        $this->composer = $composer;

        $rootPackage = $this->createMock(RootPackage::class);

        $this->rootPackage = $rootPackage;
        $this->rootPackage
            ->method('getExtra')
            ->willReturnCallback(function (): array {
                return $this->rootPackageExtra;
            });

        $io       = $this->createMock(IOInterface::class);
        $this->io = $io;

        if (false === PackageProviderDetectionFactory::isComposerV1()) {
            $config = $this->createMock(Config::class);
            $this->composer
                ->method('getConfig')
                ->willReturn($config);
            $repositoryManager = $this->createMock(RepositoryManager::class);
            $localRepository   = $this->createMock(InstalledRepositoryInterface::class);
            $localRepository
                ->method('getPackages')
                ->willReturn([]);
            $repositoryManager
                ->method('getLocalRepository')
                ->willReturn($localRepository);
            $this->composer
                ->method('getRepositoryManager')
                ->willReturn($repositoryManager);

            $this->rootPackage
                ->method('setRepository')
                ->with(self::callback(static function (object $repository): bool {
                    self::assertInstanceOf(RootPackageRepository::class, $repository);
                    return true;
                }));
        }

        $this->composer
            ->method('getPackage')
            ->willReturn($this->rootPackage);

        $this->installer->activate(
            $this->composer,
            $this->io
        );

        $installationManager       = $this->createMock(InstallationManager::class);
        $this->installationManager = $installationManager;

        $this->composer
            ->method('getInstallationManager')
            ->willReturn($installationManager);
    }

    /**
     * @param mixed $argument
     */
    public static function assertPrompt($argument, ?string $packageName = null): bool
    {
        if (! is_string($argument)) {
            return false;
        }

        if (false !== strpos($argument, 'Remember this option for other packages of the same type?')) {
            return true;
        }

        if (! $packageName) {
            return false;
        }

        if (
            false === strpos(
                $argument,
                sprintf("Please select which config file you wish to inject '%s' into", $packageName)
            )
        ) {
            return false;
        }

        if (false === strpos($argument, 'Do not inject')) {
            return false;
        }

        if (false === strpos($argument, 'application.config.php')) {
            return false;
        }

        return true;
    }

    public function createApplicationConfig(?string $contents = null): void
    {
        $contents = $contents ?: $this->createApplicationConfigWithModules([]);
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    protected function createModuleClass(string $path, string $contents): void
    {
        vfsStream::newDirectory(dirname($path))
            ->at($this->projectRoot);

        vfsStream::newFile($path)
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    /**
     * Create config on demand
     *
     * @param string $name
     * @param string $contents
     */
    private function createConfigFile($name, $contents): void
    {
        vfsStream::newFile('config/' . $name)
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    /**
     * @param PackageEvent&MockObject $event
     */
    private function prepareEventForPackageProviderDetection($event, string $packageName): void
    {
        if (method_exists(PackageEvent::class, 'getPool')) {
            $pool = $this->createMock(Pool::class);
            $pool->method('whatProvides')->with($packageName)->willReturn([]);
            $event->method('getPool')->willReturn($pool);
        }
    }

    /**
     * @param array<int,string> $informations
     */
    private function createOutputAssertions(array $informations): void
    {
        $consecutiveArguments = [];

        foreach ($informations as $information) {
            $consecutiveArguments[] = [
                self::callback(static function (string $argument) use ($information): bool {
                    return preg_match(
                        sprintf('/%s/', preg_quote($argument, '/')),
                        $information
                    ) !== false;
                }),
            ];
        }

        $this->io
            ->expects(self::exactly(count($consecutiveArguments)))
            ->method('write')
            ->withConsecutive(...$consecutiveArguments);
    }

    /**
     * @param array<int,AbstractQuestionAssertion> $questionsAssertions
     */
    private function createInputAssertions(array $questionsAssertions): void
    {
        $consecutiveReturnValues = $consecutiveArguments = [];
        foreach ($questionsAssertions as $questionAssertion) {
            $consecutiveArguments[]    = [
                self::callback($questionAssertion->assertion()),
            ];
            $consecutiveReturnValues[] = $questionAssertion->expectedAnswer;

            if ($questionAssertion instanceof RememberedAnswerQuestionAssertion) {
                $consecutiveArguments[]    = [self::callback($questionAssertion->rememberAnswerAssertion())];
                $consecutiveReturnValues[] = $questionAssertion->remember ? 'y' : 'n';
            }
        }

        $this->io
            ->expects(self::exactly(count($consecutiveArguments)))
            ->method('ask')
            ->withConsecutive(...$consecutiveArguments)
            ->willReturnOnConsecutiveCalls(...$consecutiveReturnValues);
    }

    /**
     * @psalm-param list<non-empty-string> $modulesToRegister
     * @psalm-return non-empty-string
     */
    private function createApplicationConfigWithModules(array $modulesToRegister): string
    {
        $modules = "";
        if ($modulesToRegister) {
            $modules = "\n        '" . implode("',\n        '", $modulesToRegister) . "',";
        }
        return '<' . "?php\nreturn [\n    'modules' => [" . $modules . "\n    ],\n];";
    }

    public function testMissingDependency(): void
    {
        $installPath = 'install/path';
        $this->createApplicationConfig(
            $this->createApplicationConfigWithModules(['SomeApplication']),
        );

        $this->createModuleClass(
            $installPath . '/src/SomeComponent/Module.php',
            <<<CONTENT
<?php
namespace SomeComponent;

class Module {
    public function getModuleDependencies()
    {
        return ['SomeDependency'];
    }
}
CONTENT
        );

        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getName')
            ->willReturn('some/component');

        $package
            ->method('getExtra')
            ->willReturn([
                'laminas' => [
                    'component' => 'SomeComponent',
                ],
            ]);
        $package
            ->method('getAutoload')
            ->willReturn([
                'psr-0' => [
                    'SomeComponent\\' => 'src/',
                ],
            ]);

        $this->installationManager
            ->method('getInstallPath')
            ->with($package)
            ->willReturn(vfsStream::url('project/' . $installPath));

        $operation = $this->createMock(InstallOperation::class);
        $operation
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->rootPackage
            ->method('getName')
            ->willReturn('some/component');

        $this->createOutputAssertions([
            'Installing SomeComponent from package some/component',
            'Dependency SomeDependency is not registered in the configuration',
        ]);

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject(
                'SomeComponent',
                1,
                true
            ),
        ]);

        $this->installer->onPostPackageInstall($event);
    }

    /**
     * @psalm-return array<non-empty-string, array{
     *     0: string,
     *     1: list<non-empty-string>,
     *     2: list<non-empty-string>,
     *     3: list<non-empty-string>,
     *     4: "psr-0"|"psr-4"|"classmap"|"files",
     *     5: null|string
     * }>
     */
    public function dependency(): array
    {
        return [
            // 'description' => [
            //   'package name to install',
            //   [enabled modules],
            //   [dependencies],
            //   [result: enabled modules in order],
            //   autoloading: psr-0, psr-4, classmap or files
            //   autoloadPath: only for classmap
            // ],
            'one-dependency-on-top-psr-0'               => [
                'MyPackage1',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage1', 'App'],
                'psr-0',
                null,
            ],
            'one-dependency-on-bottom-psr-0'            => [
                'MyPackage2',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage2'],
                'psr-0',
                null,
            ],
            'no-dependencies-psr-0'                     => [
                'MyPackage3',
                ['App'],
                [],
                ['MyPackage3', 'App'],
                'psr-0',
                null,
            ],
            'two-dependencies-psr-0'                    => [
                'MyPackage4',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage4', 'App'],
                'psr-0',
                null,
            ],
            'two-dependencies-in-reverse-order-psr-0'   => [
                'MyPackage5',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage5', 'App'],
                'psr-0',
                null,
            ],
            'two-dependencies-with-more-packages-psr-0' => [
                'MyPackage6',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage6', 'App2'],
                'psr-0',
                null,
            ],
            // PSR-4 autoloading
            'one-dependency-on-top-psr-4'               => [
                'MyPackage11',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage11', 'App'],
                'psr-4',
                null,
            ],
            'one-dependency-on-bottom-psr-4'            => [
                'MyPackage12',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage12'],
                'psr-4',
                null,
            ],
            'no-dependencies-psr-4'                     => [
                'MyPackage13',
                ['App'],
                [],
                ['MyPackage13', 'App'],
                'psr-4',
                null,
            ],
            'two-dependencies-psr-4'                    => [
                'MyPackage14',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage14', 'App'],
                'psr-4',
                null,
            ],
            'two-dependencies-in-reverse-order-psr-4'   => [
                'MyPackage15',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage15', 'App'],
                'psr-4',
                null,
            ],
            'two-dependencies-with-more-packages-psr-4' => [
                'MyPackage16',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage16', 'App2'],
                'psr-4',
                null,
            ],
            // classmap autoloading - dir
            'one-dependency-on-top-classmap'               => [
                'MyPackage21',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage21', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'one-dependency-on-bottom-classmap'            => [
                'MyPackage22',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage22'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'no-dependencies-classmap'                     => [
                'MyPackage23',
                ['App'],
                [],
                ['MyPackage23', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-classmap'                    => [
                'MyPackage24',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage24', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-in-reverse-order-classmap'   => [
                'MyPackage25',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage25', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-with-more-packages-classmap' => [
                'MyPackage26',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage26', 'App2'],
                'classmap',
                'path-classmap/to/module/',
            ],
            // classmap autoloading - file
            'one-dependency-on-top-classmap-file'               => [
                'MyPackage31',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage31', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'one-dependency-on-bottom-classmap-file'            => [
                'MyPackage32',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage32'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'no-dependencies-classmap-file'                     => [
                'MyPackage33',
                ['App'],
                [],
                ['MyPackage33', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-classmap-file'                    => [
                'MyPackage34',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage34', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-in-reverse-order-classmap-file'   => [
                'MyPackage35',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage35', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-with-more-packages-classmap-file' => [
                'MyPackage36',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage36', 'App2'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            // files autoloading
            'one-dependency-on-top-files'               => [
                'MyPackage41',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage41', 'App'],
                'files',
                null,
            ],
            'one-dependency-on-bottom-files'            => [
                'MyPackage42',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage42'],
                'files',
                null,
            ],
            'no-dependencies-files'                     => [
                'MyPackage43',
                ['App'],
                [],
                ['MyPackage43', 'App'],
                'files',
                null,
            ],
            'two-dependencies-files'                    => [
                'MyPackage44',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage44', 'App'],
                'files',
                null,
            ],
            'two-dependencies-in-reverse-order-files'   => [
                'MyPackage45',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage45', 'App'],
                'files',
                null,
            ],
            'two-dependencies-with-more-packages-files' => [
                'MyPackage46',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage46', 'App2'],
                'files',
                null,
            ],
        ];
    }

    /**
     * @dataProvider dependency
     * @param string $autoloading classmap|files|psr-0|psr-4
     * @psalm-param list<non-empty-string> $enabledModules
     * @psalm-param list<non-empty-string> $dependencies
     * @psalm-param list<non-empty-string> $result
     */
    public function testInjectModuleWithDependencies(
        string $packageName,
        array $enabledModules,
        array $dependencies,
        array $result,
        string $autoloading,
        ?string $autoloadPath = null
    ): void {
        $installPath = 'install/path';

        $this->createApplicationConfig($this->createApplicationConfigWithModules($enabledModules));

        switch ($autoloading) {
            case 'classmap':
                $pathToModule = 'path-classmap/to/module';
                $autoload     = [
                    $autoloadPath,
                ];
                break;
            case 'files':
                $pathToModule = 'path/to/module';
                $autoload     = [
                    'path/to/module/Module.php',
                ];
                break;
            case 'psr-0':
                $pathToModule = sprintf('src/%s', $packageName);
                $autoload     = [
                    $packageName . '\\' => 'src/',
                ];
                break;
            case 'psr-4':
                $pathToModule = 'src';
                $autoload     = [
                    $packageName . '\\' => 'src/',
                ];
                break;
        }

        $dependenciesStr = $dependencies ? "'" . implode("', '", $dependencies) . "'" : '';
        $this->createModuleClass(
            sprintf('%s/%s/Module.php', $installPath, $pathToModule),
            <<<CONTENT
                <?php
                namespace $packageName;
                
                class Module {
                    public function getModuleDependencies()
                    {
                        return [$dependenciesStr];
                    }
                }
                CONTENT
        );

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => $packageName,
            ],
        ]);
        $package->method('getAutoload')->willReturn([
            $autoloading => $autoload,
        ]);

        $this->installationManager
            ->method('getInstallPath')
            ->with($package)
            ->willReturn(vfsStream::url('project/' . $installPath));

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->createOutputAssertions([
            sprintf('Installing %s from package some/component', $packageName),
        ]);
        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject($packageName, 1, true),
        ]);

        $this->installer->onPostPackageInstall($event);

        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        self::assertEquals($result, $modules);
    }

    /**
     * @psalm-return array<non-empty-string, array{
     *     0: list<non-empty-string>,
     *     1: list<non-empty-string>,
     *     2: list<non-empty-string>
     * }>
     */
    public function modules(): array
    {
        return [
            // 'description' => [
            //   [available application modules],
            //   [enabled modules in order],
            //   [result: expected enabled modules in order],
            // ],
            'two-application-modules'                                   => [
                ['App1', 'App2'],
                ['App1', 'App2'],
                ['SomeModule', 'App1', 'App2'],
            ],
            'with-some-component'                                       => [
                ['App1'],
                ['SomeComponent', 'App1'],
                ['SomeComponent', 'SomeModule', 'App1'],
            ],
            'two-application-modules-with-some-component'               => [
                ['App1', 'App2'],
                ['SomeComponent', 'App1', 'App2'],
                ['SomeComponent', 'SomeModule', 'App1', 'App2'],
            ],
            'two-application-modules-with-some-component-another-order' => [
                ['App1', 'App2'],
                ['SomeComponent', 'App2', 'App1'],
                ['SomeComponent', 'SomeModule', 'App2', 'App1'],
            ],
            'component-between-application-modules'                     => [
                ['App1', 'App2'],
                ['App1', 'SomeComponent', 'App2'],
                ['SomeModule', 'App1', 'SomeComponent', 'App2'],
            ],
            'no-application-modules'                                    => [
                [],
                ['SomeComponent'],
                ['SomeComponent', 'SomeModule'],
            ],
        ];
    }

    /**
     * @dataProvider modules
     * @param array $availableModules
     * @param array $enabledModules
     * @param array $result
     * @psalm-param list<non-empty-string> $availableModules
     * @psalm-param list<non-empty-string> $enabledModules
     * @psalm-param list<non-empty-string> $result
     */
    public function testModuleBeforeApplicationModules(
        array $availableModules,
        array $enabledModules,
        array $result
    ): void {
        $modulePath = vfsStream::newDirectory('module')->at($this->projectRoot);
        foreach ($availableModules as $module) {
            vfsStream::newDirectory($module)->at($modulePath);
        }

        $this->createApplicationConfig(
            $this->createApplicationConfigWithModules($enabledModules)
        );

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/module');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'module' => 'SomeModule',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/module');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->createOutputAssertions([
            'Installing SomeModule from package some/module',
        ]);
        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('SomeModule', 1, true),
        ]);

        $this->installer->onPostPackageInstall($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        self::assertEquals($result, $modules);
    }

    public function testSubscribesToExpectedEvents(): void
    {
        self::assertEquals([
            'post-package-install'   => 'onPostPackageInstall',
            'post-package-uninstall' => 'onPostPackageUninstall',
            'post-package-update'    => 'onPostPackageUpdate',
        ], ComponentInstaller::getSubscribedEvents());
    }

    public function testOnPostPackageInstallReturnsEarlyIfEventIsNotInDevMode(): void
    {
        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(false);
        $event->expects(self::never())->method('getOperation');

        $this->installer->onPostPackageInstall($event);
    }

    public function testPostPackageInstallDoesNothingIfComposerExtraIsEmpty(): void
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $this->installer->onPostPackageInstall($event);
    }

    public function testOnPostPackageInstallReturnsEarlyIfApplicationConfigIsMissing(): void
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component'       => 'Some\\Component',
                'config-provider' => 'Some\\Component\\ConfigProvider',
                'module'          => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $this->installer->onPostPackageInstall($event);
    }

    public function testPostPackageInstallDoesNothingIfLaminasExtraSectionDoesNotContainComponentOrModule(): void
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn(['laminas' => []]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $this->installer->onPostPackageInstall($event);
    }

    public function testOnPostPackageInstallDoesNotPromptIfPackageIsAlreadyInConfiguration(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules(['Some\Component']));

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');
        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->io
            ->expects(self::never())
            ->method('ask');

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallDoesNotPromptForWhitelistedPackages(): void
    {
        $this->createApplicationConfig();

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->rootPackage->method('getName')->willReturn('some/component');
        $this->rootPackageExtra = [
            'laminas' => [
                'component-whitelist' => ['some/component'],
            ],
        ];

        $this->createOutputAssertions([
            'Installing Some\Component from package some/component',
        ]);
        $this->io
            ->expects(self::never())
            ->method('ask');

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallPromptsForConfigOptions(): void
    {
        $this->createApplicationConfig();

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

                $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\\Component', 1, true),
        ]);
        $this->createOutputAssertions([
            'Installing Some\Component from package some/component',
        ]);

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallPromptsForConfigOptionsWhenDefinedAsArrays(): void
    {
        $this->createApplicationConfig();

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => [
                    'Some\\Component',
                    'Other\\Component',
                ],
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\Component', 1, false),
            RememberedAnswerQuestionAssertion::inject('Other\Component', 1, false),
        ]);
        $this->createOutputAssertions([
            'Installing Some\Component from package some/component',
            'Installing Other\Component from package some/component',
        ]);

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);
        self::assertStringContainsString("'Other\Component'", $config);
    }

    public function testAddPackageToConfigWillPassProjectRootAsStringToConfigDiscovery(): void
    {
        $installationManager = $this->createMock(InstallationManager::class);

        $composer = $this->createMock(Composer::class);
        $composer
            ->method('getInstallationManager')
            ->willReturn($installationManager);

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects(self::never())
            ->method(self::anything());

        $installer = new ComponentInstaller();
        $installer->activate(
            $composer,
            $io
        );

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component'       => 'Some\\Component',
                'config-provider' => 'Some\\Component\\ConfigProvider',
                'module'          => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $installer->onPostPackageInstall($event);
    }

    public function testMultipleInvocationsOfOnPostPackageInstallCanPromptMultipleTimes(): void
    {
        // Do a first pass, with an initial package
        $this->createApplicationConfig();

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\\Component', 1, false),
            RememberedAnswerQuestionAssertion::inject('Other\\Component', 1, true),
        ]);
        $this->createOutputAssertions([
            'Installing Some\Component from package some/component',
            'Installing Other\Component from package other/component',
        ]);

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('other/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Other\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'other/component');

        $this->rootPackage->method('getName')->willReturn('other/component');

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Other\Component'", $config);
    }

    public function testMultipleInvocationsOfOnPostPackageInstallCanReuseOptions(): void
    {
        // Do a first pass, with an initial package
        $this->createApplicationConfig();

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\Component', 1, true),
        ]);

        $this->createOutputAssertions([
            'Installing Some\Component from package some/component',
            'Installing Other\Component from package other/component',
        ]);

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('other/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Other\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'other/component');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->installer->onPostPackageInstall($event);
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringContainsString("'Other\Component'", $config);
    }

    public function testOnPostPackageUninstallReturnsEarlyIfEventIsNotInDevMode(): void
    {
        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(false);
        $event
            ->expects(self::never())
            ->method('getOperation');

        $this->installer->onPostPackageUninstall($event);
    }

    public function testOnPostPackageUninstallReturnsEarlyIfNoRelevantConfigFilesAreFound(): void
    {
        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $operation = $this->createMock(UninstallOperation::class);
        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $event
            ->method('getOperation')
            ->willReturn($operation);

        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getName')
            ->willReturn('some/component');

        $operation
            ->method('getPackage')
            ->willReturn($package);

        $package
            ->method('getExtra')
            ->willReturn(['laminas' => ['component' => 'Some\Component']]);

        $this->installer->onPostPackageUninstall($event);
    }

    public function testOnPostPackageUninstallRemovesPackageFromConfiguration(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules(['Some\Component']));

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(UninstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->createOutputAssertions([
            '<info>    Removing Some\Component from package some/component</info>',
            'Removed package from .*?config/application.config.php',
        ]);

        $this->installer->onPostPackageUninstall($event);

        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringNotContainsString('Some\Component', $config);
    }

    public function testOnPostPackageUninstallCanRemovePackageArraysFromConfiguration(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules([
            'Some\Component',
            'Other\Component',
        ]));

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => [
                    'Some\\Component',
                    'Other\\Component',
                ],
            ],
        ]);

        $operation = $this->createMock(UninstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->createOutputAssertions([
            '<info>    Removing Some\Component from package some/component</info>',
            '<info>    Removing Other\Component from package some/component</info>',
            '#Removed package from .*?config/application.config.php#',
            '#Removed package from .*?config/application.config.php#',
        ]);

        $this->installer->onPostPackageUninstall($event);

        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringNotContainsString('Some\Component', $config);
        self::assertStringNotContainsString('Other\Component', $config);
    }

    public function testModuleIsAppended(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules(['Some\Component']));

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/module');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'module' => 'Some\\Module',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/module');

        $this->rootPackage->method('getName')->willReturn('some/module');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\Module', 1, true),
        ]);

        $this->createOutputAssertions([
            'Installing Some\Module from package some/module',
        ]);

        $this->installer->onPostPackageInstall($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        self::assertEquals([
            'Some\Component',
            'Some\Module',
        ], $modules);
    }

    public function testAppendModuleAndPrependComponent(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules(['SomeApplication']));

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/package');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'module'    => 'Some\\Module',
                'component' => 'Some\\Component',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/package');

        $this->rootPackage->method('getName')->willReturn('some/package');

        $this->createInputAssertions([
            RememberedAnswerQuestionAssertion::inject('Some\Component', 1, false),
            RememberedAnswerQuestionAssertion::inject('Some\Module', 1, false),
        ]);

        $this->createOutputAssertions([
            'Installing Some\Component from package some/package',
            'Installing Some\Module from package some/package',
        ]);

        $this->installer->onPostPackageInstall($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        self::assertEquals([
            'Some\Component',
            'SomeApplication',
            'Some\Module',
        ], $modules);
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: array<string, array<array-key, string>>
     * }>
     */
    public function moduleClass(): array
    {
        return [
            [__DIR__ . '/TestAsset/ModuleBadlyFormatted.php', ['BadlyFormatted\Application' => ['Dependency1']]],
            [__DIR__ . '/TestAsset/ModuleWithDependencies.php', ['MyNamespace' => ['Dependency']]],
            [__DIR__ . '/TestAsset/ModuleWithInterface.php', ['LongArray\Application' => ['Foo\D1', 'Bar\D2']]],
            [__DIR__ . '/TestAsset/ModuleWithoutDependencies.php', []],
            [__DIR__ . '/TestAsset/ModuleWithEmptyArrayDependencies.php', []],
        ];
    }

    /**
     * @dataProvider moduleClass
     * @psalm-param array<string, array<array-key, string>> $result
     */
    public function testGetModuleDependenciesFromModuleClass(string $file, array $result): void
    {
        $r  = new ReflectionObject($this->installer);
        $rm = $r->getMethod('getModuleDependencies');
        $rm->setAccessible(true);

        $dependencies = $rm->invoke($this->installer, $file);

        self::assertEquals($result, $dependencies);
    }

    public function testGetModuleClassesDependenciesHandlesAutoloadersWithMultiplePathsMappedToSameNamespace(): void
    {
        $installPath = 'install/path';
        $this->setUpModuleDependencies($installPath);

        $autoloaders = [
            'psr-0'    => [
                'DoesNotExist\\' => [
                    'src/Psr0/',
                    'src/Psr0Too/',
                ],
            ],
            'psr-4'    => [
                'DoesNotExistEither\\' => [
                    'src/Psr4/',
                    'src/Psr4Too/',
                ],
            ],
            'classmap' => [
                'src/Classmapped/',
                'src/ClassmappedToo/',
            ],
            'files'    => [
                'src/File/Module.php',
                'src/FileToo/Module.php',
            ],
        ];

        $package = $this->createMock(PackageInterface::class);
        $package->method('getAutoload')->willReturn($autoloaders);

        $this->installationManager
            ->method('getInstallPath')
            ->with($package)
            ->willReturn(vfsStream::url('project/' . $installPath));

        $r  = new ReflectionObject($this->installer);
        $rm = $r->getMethod('loadModuleClassesDependencies');
        $rm->setAccessible(true);

        $dependencies = $rm->invoke($this->installer, $package);
        self::assertEquals([
            'DoesNotExist'       => ['DoesNotExistDependency'],
            'DoesNotExistEither' => ['DoesNotExistEitherDependency'],
            'ClassmappedToo'     => ['ClassmappedTooDependency'],
            'File'               => ['FileDependency'],
        ], $dependencies);
    }

    public function setUpModuleDependencies(string $path): void
    {
        $this->createModuleClass(
            $path . '/src/Psr0Too/DoesNotExist/Module.php',
            <<<CONTENT
<?php
namespace DoesNotExist;

class Module
{
    public function getModuleDependencies()
    {
        return ['DoesNotExistDependency'];
    }
}
CONTENT
        );

        $this->createModuleClass(
            $path . '/src/Psr4/Module.php',
            <<<CONTENT
<?php
namespace DoesNotExistEither;

class Module
{
    public function getModuleDependencies()
    {
        return ['DoesNotExistEitherDependency'];
    }
}
CONTENT
        );

        mkdir(sprintf('%s/%s/src/ClassmappedToo', vfsStream::url('project'), $path));
        $this->createModuleClass(
            $path . '/src/ClassmappedToo/Module.php',
            <<<CONTENT
<?php
namespace ClassmappedToo;

class Module
{
    public function getModuleDependencies()
    {
        return ['ClassmappedTooDependency'];
    }
}
CONTENT
        );

        $this->createModuleClass(
            $path . '/src/File/Module.php',
            <<<CONTENT
<?php
namespace File;

class Module
{
    public function getModuleDependencies()
    {
        return ['FileDependency'];
    }
}
CONTENT
        );
    }

    /**
     * @dataProvider injectorConfigProvider
     * @param string $configContents
     * @param array $configNames
     * @param string $expectedName
     */
    public function testUninstallMessageWithDifferentInjectors($configContents, array $configNames, $expectedName): void
    {
        foreach ($configNames as $configName) {
            $this->createConfigFile($configName, $configContents);
        }

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => [
                    'Some\\Component',
                ],
            ],
        ]);

        $operation = $this->createMock(UninstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->createOutputAssertions([
            '<info>    Removing Some\Component from package some/component</info>',
            sprintf('Removed package from %s', $expectedName),
        ]);

        $this->installer->onPostPackageUninstall($event);
    }

    public function testInstallWhitelistedDevModuleWithDifferentInjectors(): void
    {
        $moduleConfigContent = <<<'CONFIG'
<?php
return [
    'modules' => [
        'Laminas\Router',
        'Laminas\Validator',
        'Application'
    ]
];
CONFIG;

        $this->createConfigFile('modules.config.php', $moduleConfigContent);

        $configContents = <<<'CONFIG'
<?php
return [
    'modules' => [
    ]
];
CONFIG;
        foreach (['development.config.php.dist', 'development.config.php'] as $configName) {
            $this->createConfigFile($configName, $configContents);
        }

        $this->rootPackage->method('getDevRequires')->willReturn(['some/component' => '*']);
        $this->rootPackageExtra = [
            'laminas' => [
                "component-whitelist" => [
                    "some/component",
                ],
            ],
        ];

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/component');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'component' => [
                    'Some\\Component',
                ],
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/component');

        $this->installer->onPostPackageInstall($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/modules.config.php');
        $modules = $config['modules'];
        self::assertEquals([
            'Laminas\Router',
            'Laminas\Validator',
            'Application',
        ], $modules);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/development.config.php');
        $modules = $config['modules'];
        self::assertEquals(['Some\Component'], $modules);
    }

    public function testInstallWhitelistedDevModuleWithUniqueInjector(): void
    {
        $moduleConfigContent = <<<'CONFIG'
<?php
return [
    'modules' => [
        'Laminas\Router',
        'Laminas\Validator',
        'Application',
    ]
];
CONFIG;

        $this->createConfigFile('modules.config.php', $moduleConfigContent);

        $this->rootPackageExtra = [
            'laminas' => [
                "component-whitelist" => [
                    "some/module",
                ],
            ],
        ];

        $package = $this->createMock(PackageInterface::class);
        $package->method('getName')->willReturn('some/module');
        $package->method('getExtra')->willReturn([
            'laminas' => [
                'module' => 'Some\\Module',
            ],
        ]);

        $operation = $this->createMock(InstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/module');

        $this->rootPackage
            ->method('getName')
            ->willReturn('some/module');

        $this->installer->onPostPackageInstall($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/modules.config.php');
        $modules = $config['modules'];
        self::assertEquals([
            'Laminas\Router',
            'Laminas\Validator',
            'Application',
            'Some\Module',
        ], $modules);
    }

    /**
     * @return array
     */
    public function injectorConfigProvider()
    {
        $config = <<<'CONFIG'
<?php
return [
    'modules' => [
        'Some\Component'
    ]
];
CONFIG;

        return [
            'application.config.php' => [
                $config,
                ['application.config.php'],
                '.*?config/application.config.php',
            ],
            'development.config.php' => [
                $config,
                ['development.config.php.dist', 'development.config.php'],
                '.*?config/development.config.php.dist.*?config/development.config.php',
            ],
        ];
    }

    public function testOnPostPackageUpdateReturnsEarlyIfEventIsNotInDevMode(): void
    {
        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(false);
        $event->expects(self::never())->method('getOperation');

        $this->installer->onPostPackageUpdate($event);
    }

    public function testOnPostPackageUpdateDoesNothingIfComposerExtraIsEmpty(): void
    {
        $initialPackage = $this->createMock(PackageInterface::class);
        $initialPackage->method('getName')->willReturn('some/component');
        $targetPackage = $this->createMock(PackageInterface::class);
        $targetPackage->method('getName')->willReturn('some/component');

        $operation = $this->createMock(UpdateOperation::class);
        $operation->method('getInitialPackage')->willReturn($initialPackage);
        $operation->method('getTargetPackage')->willReturn($targetPackage);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $this->installer->onPostPackageUpdate($event);
    }

    public function testOnPostPackageUpdateDoesNothingIfComposerExtraEquals(): void
    {
        $initialPackage = $this->createMock(PackageInterface::class);
        $initialPackage
            ->expects(self::once())
            ->method('getExtra')
            ->willReturn(['laminas' => ['component' => 'Some\Component']]);
        $targetPackage = $this->createMock(PackageInterface::class);
        $targetPackage
            ->expects(self::once())
            ->method('getExtra')
            ->willReturn(['laminas' => ['component' => 'Some\Component']]);

        $operation = $this->createMock(UpdateOperation::class);
        $operation->method('getInitialPackage')->willReturn($initialPackage);
        $operation->method('getTargetPackage')->willReturn($targetPackage);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->io
            ->expects(self::never())
            ->method(self::anything());

        $this->installer->onPostPackageUpdate($event);
    }

    public function testOnPostPackageUpdateRemovesPackageWhenNewerVersionDoesNotContainExtraAnymore(): void
    {
        $this->createApplicationConfig($this->createApplicationConfigWithModules(['Some\Component']));

        $initialPackage = $this->createMock(PackageInterface::class);
        $initialPackage
            ->expects(self::once())
            ->method('getExtra')
            ->willReturn(['laminas' => ['component' => 'Some\Component']]);
        $targetPackage = $this->createMock(PackageInterface::class);

        $operation = $this->createMock(UpdateOperation::class);
        $operation->method('getInitialPackage')->willReturn($initialPackage);
        $operation->method('getTargetPackage')->willReturn($targetPackage);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);

        $this->createOutputAssertions([
            '<info>    Removing Some\Component from package some/component</info>',
            'Removed package from .*?config/application.config.php',
        ]);

        $this->installer->onPostPackageUpdate($event);

        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        self::assertStringNotContainsString('Some\Component', $config);
    }

    /**
     * @psalm-param list<non-empty-string> $installedModules
     * @psalm-param ComposerExtraLaminasConfiguration $previousExtra
     * @psalm-param ComposerExtraLaminasConfiguration $newExtra
     * @psalm-param list<AbstractQuestionAssertion> $inputAssertions
     * @psalm-param list<non-empty-string> $outputAssertions
     * @psalm-param list<non-empty-string> $expectedInstalledModules
     * @dataProvider packageUpdateScenarios
     */
    public function testOnPostPackageUpdateAddsPackageWhenNewerVersionContainsDifferentInformationsThanPreviousVersion(
        array $installedModules,
        array $previousExtra,
        array $newExtra,
        array $inputAssertions,
        array $outputAssertions,
        array $expectedInstalledModules
    ): void {
        $this->createApplicationConfig($this->createApplicationConfigWithModules($installedModules));

        $previousPackage = $this->createMock(PackageInterface::class);
        $previousPackage->method('getName')->willReturn('some/module');
        $previousPackage->method('getExtra')->willReturn($previousExtra);

        $newPackage = $this->createMock(PackageInterface::class);
        $newPackage->method('getName')->willReturn('some/module');
        $newPackage->method('getExtra')->willReturn($newExtra);

        $operation = $this->createMock(UpdateOperation::class);
        $operation->method('getInitialPackage')->willReturn($previousPackage);
        $operation->method('getTargetPackage')->willReturn($newPackage);

        $event = $this->createMock(PackageEvent::class);
        $event->method('isDevMode')->willReturn(true);
        $event->method('getOperation')->willReturn($operation);
        $this->prepareEventForPackageProviderDetection($event, 'some/module');

        $this->rootPackage->method('getName')->willReturn('some/component');

        $this->createOutputAssertions($outputAssertions);
        $this->createInputAssertions($inputAssertions);

        $this->installer->onPostPackageUpdate($event);
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array{modules:list<non-empty-string>} $config
         */
        $config  = require vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        self::assertEquals($expectedInstalledModules, $modules);
    }

    /**
     * @psalm-return Generator<non-empty-string,array{
     *     0:list<non-empty-string>,
     *     1:ComposerExtraLaminasConfiguration,
     *     2:ComposerExtraLaminasConfiguration,
     *     3:list<AbstractQuestionAssertion>,
     *     4:list<non-empty-string>,
     *     5:list<non-empty-string>
     * }>
     */
    public function packageUpdateScenarios(): Generator
    {
        yield 'package introduces module' => [
            [], // Initially installed application modules
            [], // Initial configuration had no composer extra information for this component
            [
                'laminas' => [
                    'module' => 'Some\Module',
                ],
            ],
            [
                RememberedAnswerQuestionAssertion::inject('Some\Module', 1, true),
            ],
            [
                'Installing Some\Module from package',
            ],
            [
                'Some\Module',
            ],
        ];

        yield 'package introduces component' => [
            [], // Initially installed application modules
            [], // Initial configuration had no composer extra information for this component
            [
                'laminas' => [
                    'component' => 'Some\Component',
                ],
            ],
            [
                RememberedAnswerQuestionAssertion::inject('Some\Component', 1, true),
            ],
            [
                'Installing Some\Component from package',
            ],
            [
                'Some\Component',
            ],
        ];

        yield 'new version drops module but keeps component' => [
            ['Some\Component', 'Some\Module'],
            [
                'laminas' => [
                    'module'    => 'Some\Module',
                    'component' => 'Some\Component',
                ],
            ],
            [
                'laminas' => [
                    'component' => 'Some\Component',
                ],
            ],
            [],
            [
                '<info>    Removing Some\Module from package',
                'Removed package from .*?config/application.config.php',
            ],
            [
                'Some\Component',
            ],
        ];
        yield 'new version drops component but keeps module' => [
            ['Some\Component', 'Some\Module'],
            [
                'laminas' => [
                    'module'    => 'Some\Module',
                    'component' => 'Some\Component',
                ],
            ],
            [
                'laminas' => [
                    'module' => 'Some\Module',
                ],
            ],
            [],
            [
                '<info>    Removing Some\Component from package',
                'Removed package from .*?config/application.config.php',
            ],
            [
                'Some\Module',
            ],
        ];
    }
}
