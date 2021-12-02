<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\ConfigAggregator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class ConfigAggregatorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $configDir;

    /** @var ConfigAggregator */
    private $locator;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new ConfigAggregator(
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
     * @psalm-return array<string, array{
     *     0: string
     * }>
     */
    public function validMezzioConfigContents(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'fqcn-short-array'               => ['<' . "?php\n\$aggregator = new Laminas\ConfigAggregator\ConfigAggregator([\n]);"],
            'globally-qualified-short-array' => ['<' . "?php\n\$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([\n]);"],
            'imported-short-array'           => ['<' . "?php\n\$aggregator = new ConfigAggregator([\n]);"],
            'fqcn-long-array'                => ['<' . "?php\n\$aggregator = new Laminas\ConfigAggregator\ConfigAggregator(array(\n));"],
            'globally-qualified-long-array'  => ['<' . "?php\n\$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator(array(\n));"],
            'imported-long-array'            => ['<' . "?php\n\$aggregator = new ConfigAggregator(array(\n));"],
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
