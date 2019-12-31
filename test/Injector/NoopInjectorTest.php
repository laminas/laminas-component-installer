<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\Injector;

use Composer\IO\IOInterface;
use Laminas\ComponentInstaller\Injector\NoopInjector;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class NoopInjectorTest extends TestCase
{
    public function setUp()
    {
        $this->injector = new NoopInjector();
    }

    /**
     * @dataProvider packageTypes
     */
    public function testWillRegisterAnyType($type)
    {
        $this->assertTrue($this->injector->registersType($type), 'NoopInjector does not register type ' . $type);
    }

    public function testGetTypesAllowedReturnsNoTypes()
    {
        $this->assertEquals([], $this->injector->getTypesAllowed());
    }

    public function packageTypes()
    {
        return [
            'config-provider' => [NoopInjector::TYPE_CONFIG_PROVIDER],
            'component'       => [NoopInjector::TYPE_COMPONENT],
            'module'          => [NoopInjector::TYPE_MODULE],
        ];
    }

    /**
     * @dataProvider packageTypes
     */
    public function testInjectIsANoop($type)
    {
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::any())->shouldNotBeCalled();
        $this->assertNull($this->injector->inject('Foo\Bar', $type, $io->reveal()));
    }

    /**
     * @dataProvider packageTypes
     */
    public function testRemoveIsANoop($type)
    {
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::any())->shouldNotBeCalled();
        $this->assertNull($this->injector->remove('Foo\Bar', $io->reveal()));
    }
}
