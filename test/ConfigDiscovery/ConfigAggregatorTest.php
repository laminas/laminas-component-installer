<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

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

    public function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new ConfigAggregator(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate()
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    public function validMezzioConfigContents()
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
     *
     * @param string $contents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
