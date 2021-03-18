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

    protected function setUp(): void
    {
        $this->injector = new NoopInjector();
    }

    /**
     * @dataProvider packageTypes
     */
    public function testWillRegisterAnyType(int $type): void
    {
        $this->assertTrue($this->injector->registersType($type), 'NoopInjector does not register type ' . $type);
    }

    public function testGetTypesAllowedReturnsNoTypes(): void
    {
        $this->assertEquals([], $this->injector->getTypesAllowed());
    }

    /**
     * @psalm-return array<string, array{0: int}>
     */
    public function packageTypes(): array
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
    public function testInjectIsANoop(int $type): void
    {
        $injected = $this->injector->inject('Foo\Bar', $type);

        $this->assertFalse($injected);
    }

    public function testRemoveIsANoop(): void
    {
        $removed = $this->injector->remove('Foo\Bar');

        $this->assertFalse($removed);
    }
}
