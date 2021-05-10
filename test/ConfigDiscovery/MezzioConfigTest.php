<?php

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\MezzioConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class MezzioConfigTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $configDir;

    /** @var MezzioConfig */
    private $locator;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new MezzioConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate(): void
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    /**
     * @psalm-return array<string, array{0: string}>
     */
    public function validMezzioConfigContents(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'fqcn-short-array'               => ['<' . "?php\n\$configManager = new Mezzio\ConfigManager\ConfigManager([\n]);"],
            'globally-qualified-short-array' => ['<' . "?php\n\$configManager = new \Mezzio\ConfigManager\ConfigManager([\n]);"],
            'imported-short-array'           => ['<' . "?php\n\$configManager = new ConfigManager([\n]);"],
            'fqcn-long-array'                => ['<' . "?php\n\$configManager = new Mezzio\ConfigManager\ConfigManager(array(\n));"],
            'globally-qualified-long-array'  => ['<' . "?php\n\$configManager = new \Mezzio\ConfigManager\ConfigManager(array(\n));"],
            'imported-long-array'            => ['<' . "?php\n\$configManager = new ConfigManager(array(\n));"],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider validMezzioConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent(string $contents): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
