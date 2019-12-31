<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\NoopInjector;
use PHPUnit\Framework\TestCase;

class NoopInjectorTest extends TestCase
{
    /** @var NoopInjector */
    private $injector;

    public function setUp()
    {
        $this->injector = new NoopInjector();
    }

    /**
     * @dataProvider packageTypes
     *
     * @param string $type
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
     *
     * @param string $type
     */
    public function testInjectIsANoop($type)
    {
        $injected = $this->injector->inject('Foo\Bar', $type);

        $this->assertFalse($injected);
    }

    public function testRemoveIsANoop()
    {
        $removed = $this->injector->remove('Foo\Bar');

        $this->assertFalse($removed);
    }
}
