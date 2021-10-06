<?php

namespace LaminasTest\ComponentInstaller;

use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\NoopInjector;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use function array_shift;
use function get_class;
use function gettype;
use function is_object;
use function sprintf;

/**
 * @psalm-suppress MissingConstructor
 */
class ConfigDiscoveryTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $projectRoot;

    /** @var ConfigDiscovery\ */
    private $discovery;

    /** @var Collection */
    private $allTypes;

    /** @var string[] */
    private $injectorTypes;

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

    public function createApplicationConfig(): void
    {
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createDevelopmentConfig(bool $dist = true): void
    {
        $configFileName = 'config/development.config.php' . ($dist ? '.dist' : '');
        vfsStream::newFile($configFileName)
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createDevelopmentWorkConfig(): void
    {
        $this->createDevelopmentConfig(false);
    }

    public function createAggregatorConfig(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$aggregator = new ConfigAggregator([\n]);");
    }

    public function createMezzioConfig(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$configManager = new ConfigManager([\n]);");
    }

    public function createModulesConfig(): void
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n]);");
    }

    public function assertOptionsContainsNoopInjector(Collection $options): void
    {
        if ($options->isEmpty()) {
            throw new ExpectationFailedException('Options array is empty; no NoopInjector found!');
        }

        $options  = $options->toArray();
        $injector = array_shift($options)->getInjector();

        if (! $injector instanceof NoopInjector) {
            throw new ExpectationFailedException('Options array does not contain a NoopInjector!');
        }
    }

    public function assertOptionsContainsInjector(string $injectorType, Collection $options): InjectorInterface
    {
        foreach ($options as $option) {
            if (! $option instanceof ConfigOption) {
                self::fail(sprintf(
                    'Invalid option returned: %s',
                    is_object($option) ? get_class($option) : gettype($option)
                ));
            }

            if ($injectorType === get_class($option->getInjector())) {
                return $option->getInjector();
            }
        }

        self::fail(sprintf(
            'Injector of type %s was not found in the options',
            $injectorType
        ));
    }

    public function assertOptionsContainsInjectorInChain(string $injectorType, Collection $options): void
    {
        $chain = $this->assertOptionsContainsInjector(Injector\ConfigInjectorChain::class, $options);
        $this->assertInstanceOf(Injector\ConfigInjectorChain::class, $chain);

        foreach ($chain->getCollection() as $injector) {
            if (! $injector instanceof InjectorInterface) {
                self::fail(sprintf(
                    'Invalid Injector returned: %s',
                    is_object($injector) ? get_class($injector) : gettype($injector)
                ));
            }

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
     *     type: int,
     *     expected: string,
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
