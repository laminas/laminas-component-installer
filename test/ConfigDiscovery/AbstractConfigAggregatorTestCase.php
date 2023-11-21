<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

abstract class AbstractConfigAggregatorTestCase extends TestCase
{
    private vfsStreamDirectory $configDir;

    private DiscoveryInterface $locator;

    /** @var class-string<DiscoveryInterface> */
    protected string $discoveryClass;

    protected string $configFile;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new $this->discoveryClass(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate(): void
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents(): void
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    /**
     * @psalm-return array<string, array{
     *     0: string
     * }>
     */
    public static function validMezzioConfigContents(): array
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
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
