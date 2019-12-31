<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\DevelopmentConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;

class DevelopmentConfigTest extends TestCase
{
    private $configDir;

    private $locator;

    public function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new DevelopmentConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate()
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents()
    {
        vfsStream::newFile('config/development.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    public function validDevelopmentConfigContents()
    {
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n    )\n);"],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n    ]\n];"],
        ];
    }

    /**
     * @dataProvider validDevelopmentConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/development.config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
