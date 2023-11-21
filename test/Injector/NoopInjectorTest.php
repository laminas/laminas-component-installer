<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
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
     * @psalm-return array<string, array{0: InjectorInterface::TYPE_*}>
     */
    public static function packageTypes(): array
    {
        return [
            'config-provider' => [NoopInjector::TYPE_CONFIG_PROVIDER],
            'component'       => [NoopInjector::TYPE_COMPONENT],
            'module'          => [NoopInjector::TYPE_MODULE],
        ];
    }

    /**
     * @param InjectorInterface::TYPE_* $type
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
