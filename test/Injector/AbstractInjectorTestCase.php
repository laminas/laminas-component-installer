<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\AbstractInjector;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

abstract class AbstractInjectorTestCase extends TestCase
{
    /** @var vfsStreamDirectory */
    protected $configDir;

    /** @var string */
    protected $configFile;

    /** @var AbstractInjector */
    protected $injector;

    /** @var string */
    protected $injectorClass;

    /** @var int[] */
    protected $injectorTypesAllowed = [];

    protected function setUp() : void
    {
        $this->configDir = vfsStream::setup('project');

        $injectorClass = $this->injectorClass;
        $this->injector = new $injectorClass(
            vfsStream::url('project')
        );
    }

    /**
     * @psalm-return array<string, array{0: int, 1: bool}>
     */
    abstract public function allowedTypes(): array;

    /**
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
     * @psalm-return array<string, array{0: int, 1: string, 2: string}>
     */
    abstract public function injectComponentProvider(): array;

    /**
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
     * @psalm-return array<string, array{0: string, 1: int}>
     */
    abstract public function packageAlreadyRegisteredProvider(): array;

    /**
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
     * @psalm-return array<string, array{0: string}>
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
     * @psalm-return array<string, array{0: string, 1: string}>
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
