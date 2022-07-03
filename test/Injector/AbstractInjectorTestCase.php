<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

abstract class AbstractInjectorTestCase extends TestCase
{
    /** @var vfsStreamDirectory */
    protected $configDir;

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    protected $configFile;

    /** @var InjectorInterface */
    protected $injector;

    /**
     * @var string
     * @psalm-var class-string<InjectorInterface>
     */
    protected $injectorClass;

    /**
     * @var array<int,int>
     * @psalm-var list<InjectorInterface::TYPE_*>
     */
    protected $injectorTypesAllowed = [];

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');

        $injectorClass  = $this->injectorClass;
        $this->injector = new $injectorClass(
            vfsStream::url('project')
        );
    }

    /**
     * @see InjectorInterface
     *
     * @psalm-return array<non-empty-string, array{0: InjectorInterface::TYPE_*, 1: bool}>
     */
    abstract public function allowedTypes(): array;

    /**
     * @psalm-param InjectorInterface::TYPE_* $type
     * @dataProvider allowedTypes
     */
    public function testRegistersTypesReturnsExpectedBooleanBasedOnType(int $type, bool $expected): void
    {
        $this->assertSame($expected, $this->injector->registersType($type));
    }

    public function testGetTypesAllowedReturnsListOfAllExpectedTypes(): void
    {
        $this->assertEquals($this->injectorTypesAllowed, $this->injector->getTypesAllowed());
    }

    /**
     * @psalm-return array<non-empty-string, array{0: InjectorInterface::TYPE_*, 1: string, 2: string}>
     */
    abstract public function injectComponentProvider(): array;

    /**
     * @psalm-param InjectorInterface::TYPE_* $type
     * @dataProvider injectComponentProvider
     */
    public function testInjectAddsPackageToModulesListInAppropriateLocation(
        int $type,
        string $initialContents,
        string $expectedContents
    ): void {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $injected = $this->injector->inject('Foo\Bar', $type);

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertEquals($expectedContents, $result);
        $this->assertTrue($injected);
    }

    /**
     * @psalm-return array<non-empty-string, array{0: string, 1: InjectorInterface::TYPE_*}>
     */
    abstract public function packageAlreadyRegisteredProvider(): array;

    /**
     * @param InjectorInterface::TYPE_* $type
     * @dataProvider packageAlreadyRegisteredProvider
     */
    public function testInjectDoesNotModifyContentsIfPackageIsAlreadyRegistered(string $contents, int $type): void
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $injected = $this->injector->inject('Foo\Bar', $type);

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($contents, $result);
        $this->assertFalse($injected);
    }

    /**
     * @psalm-return array<non-empty-string, array{0: string}>
     */
    abstract public function emptyConfiguration(): array;

    /**
     * @dataProvider emptyConfiguration
     */
    public function testRemoveDoesNothingIfPackageIsNotInConfigFile(string $contents): void
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $removed = $this->injector->remove('Foo\Bar');
        $this->assertFalse($removed);
    }

    /**
     * @psalm-return array<non-empty-string, array{0: string, 1: string}>
     */
    abstract public function packagePopulatedInConfiguration(): array;

    /**
     * @dataProvider packagePopulatedInConfiguration
     */
    public function testRemoveRemovesPackageFromConfigurationWhenFound(
        string $initialContents,
        string $expectedContents
    ): void {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $removed = $this->injector->remove('Foo\Bar');

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($expectedContents, $result);
        $this->assertTrue($removed);
    }
}
