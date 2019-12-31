<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

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

    protected function setUp() : void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new MezzioConfig(
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
