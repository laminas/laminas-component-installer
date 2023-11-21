<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\ModulesConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class ModulesConfigTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $configDir;

    /** @var ModulesConfig */
    private $locator;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new ModulesConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate(): void
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents(): void
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn true;");
        $this->assertFalse($this->locator->locate());
    }

    /**
     * @psalm-return array<string, array{0: string}>
     */
    public static function validModulesConfigContents(): array
    {
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n);"],
            'short-array' => ['<' . "?php\nreturn [\n];"],
        ];
    }

    /**
     * @dataProvider validModulesConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent(string $contents): void
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
