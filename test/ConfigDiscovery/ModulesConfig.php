<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

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

    protected function setUp() : void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new ModulesConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate()
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents()
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn true;");
        $this->assertFalse($this->locator->locate());
    }

    public function validModulesConfigContents()
    {
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n);"],
            'short-array' => ['<' . "?php\nreturn [\n];"],
        ];
    }

    /**
     * @dataProvider validModulesConfigContents
     *
     * @param string $contents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
