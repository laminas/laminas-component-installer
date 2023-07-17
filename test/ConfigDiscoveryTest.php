<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\NoopInjector;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function get_class;
use function sprintf;

class ConfigDiscoveryTest extends TestCase
{
    private vfsStreamDirectory $projectRoot;

    private ConfigDiscovery $discovery;

    /** @var Collection<array-key,InjectorInterface::TYPE_*> */
    private Collection $allTypes;

    /** @var list<class-string<InjectorInterface>> */
    private array $injectorTypes;

    protected function setUp(): void
    {
        $this->projectRoot = vfsStream::setup('project');
        $this->discovery   = new ConfigDiscovery();

        $this->allTypes = new Collection([
            InjectorInterface::TYPE_CONFIG_PROVIDER,
            InjectorInterface::TYPE_COMPONENT,
            InjectorInterface::TYPE_MODULE,
        ]);

        $this->injectorTypes = [
            Injector\ApplicationConfigInjector::class,
            // Injector\ConfigAggregatorInjector::class,
            Injector\ConfigInjectorChain::class,
            // Injector\MezzioConfigInjector::class,
            Injector\ModulesConfigInjector::class,
        ];
    }

    private function createApplicationConfig(): void
    {
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    private function createDevelopmentConfig(bool $dist = true): void
    {
        $configFileName = 'config/development.config.php' . ($dist ? '.dist' : '');
        vfsStream::newFile($configFileName)
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    private function createDevelopmentWorkConfig(): void
    {
        $this->createDevelopmentConfig(false);
    }

    private function createMezzioDevelopmentConfig(bool $dist = true): void
    {
        $configFileName = 'config/development.config.php' . ($dist ? '.dist' : '');
        vfsStream::newFile($configFileName)
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$aggregator = new ConfigAggregator([\n]);");
    }

    private function createAggregatorConfig(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$aggregator = new ConfigAggregator([\n]);");
    }

    private function createMezzioConfig(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$configManager = new ConfigManager([\n]);");
    }

    private function createModulesConfig(): void
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n]);");
    }

    /**
     * @param Collection<int,ConfigOption> $options
     */
    public function assertOptionsContainsNoopInjector(Collection $options): void
    {
        if ($options->isEmpty()) {
            self::fail('Options array is empty; no NoopInjector found!');
        }

        $injector = $options->get(0)->getInjector();

        if (! $injector instanceof NoopInjector) {
            self::fail('Options array does not contain a NoopInjector!');
        }
    }

    /**
     * @param class-string<InjectorInterface> $injectorType
     * @param Collection<int,ConfigOption> $options
     */
    public function assertOptionsContainsInjector(string $injectorType, Collection $options): InjectorInterface
    {
        foreach ($options as $option) {
            if ($injectorType === get_class($option->getInjector())) {
                return $option->getInjector();
            }
        }

        self::fail(sprintf(
            'Injector of type %s was not found in the options',
            $injectorType
        ));
    }

    /**
     * @param class-string<InjectorInterface>    $injectorType
     * @param Collection<int,ConfigOption> $options
     */
    public function assertOptionsContainsInjectorInChain(string $injectorType, Collection $options): void
    {
        $chain = $this->assertOptionsContainsInjector(Injector\ConfigInjectorChain::class, $options);
        $this->assertInstanceOf(Injector\ConfigInjectorChain::class, $chain);

        foreach ($chain->getCollection() as $injector) {
            if ($injectorType === get_class($injector)) {
                return;
            }
        }

        self::fail(sprintf(
            'Injector of type %s was not found in the options',
            $injectorType
        ));
    }

    public function testGetAvailableConfigOptionsReturnsEmptyArrayWhenNoConfigFilesPresent(): void
    {
        $result = $this->discovery->getAvailableConfigOptions($this->allTypes);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testGetAvailableConfigOptionsReturnsOptionsForEachSupportedPackageType(): void
    {
        $this->createApplicationConfig();
        $this->createDevelopmentConfig();
        $this->createAggregatorConfig();
        $this->createMezzioDevelopmentConfig();
        $this->createMezzioConfig();
        $this->createModulesConfig();

        $options = $this->discovery->getAvailableConfigOptions($this->allTypes, vfsStream::url('project'));
        $this->assertCount(5, $options);

        $this->assertOptionsContainsNoopInjector($options);
        foreach ($this->injectorTypes as $injector) {
            $this->assertOptionsContainsInjector($injector, $options);
        }
    }

    /**
     * @psalm-return array<array-key, array{
     *     seedMethod: string,
     *     type: InjectorInterface::TYPE_*,
     *     expected: class-string<InjectorInterface>,
     *     chain: bool
     * }>
     */
    public function configFileSubset(): array
    {
        return [
            [
                'seedMethod' => 'createApplicationConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\ApplicationConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createApplicationConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ApplicationConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createAggregatorConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ConfigAggregatorInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createAggregatorConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ConfigAggregatorInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\DevelopmentConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\DevelopmentConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentWorkConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\DevelopmentWorkConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentWorkConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\DevelopmentWorkConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createMezzioConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\MezzioConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createMezzioConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\MezzioConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\ModulesConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ModulesConfigInjector::class,
                'chain'      => false,
            ],
        ];
    }

    /**
     * @dataProvider configFileSubset
     * @param InjectorInterface::TYPE_* $type
     * @param class-string<InjectorInterface> $expected
     */
    public function testGetAvailableConfigOptionsCanReturnsSubsetOfOptionsBaseOnPackageType(
        string $seedMethod,
        int $type,
        string $expected,
        bool $chain
    ): void {
        $this->{$seedMethod}();
        $options = $this->discovery->getAvailableConfigOptions(new Collection([$type]), vfsStream::url('project'));
        $this->assertCount(2, $options);

        $this->assertOptionsContainsNoopInjector($options);
        if ($chain) {
            $this->assertOptionsContainsInjectorInChain($expected, $options);
        } else {
            $this->assertOptionsContainsInjector($expected, $options);
        }
    }

    public function testNoOptionReturnedIfInjectorCannotRegisterType(): void
    {
        $this->createApplicationConfig();
        $options = $this->discovery->getAvailableConfigOptions(
            new Collection([InjectorInterface::TYPE_CONFIG_PROVIDER]),
            vfsStream::url('project')
        );

        $this->assertInstanceOf(Collection::class, $options);
        $this->assertTrue($options->isEmpty());
    }
}
