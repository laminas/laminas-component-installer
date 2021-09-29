<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\AbstractDiscovery;
use PHPUnit\Framework\TestCase;

class AbstractDiscoveryTest extends TestCase
{
    /**
     * @covers \Laminas\ComponentInstaller\ConfigDiscovery\AbstractDiscovery::__construct
     */
    public function testConstructorThrowsExceptionWithProjectDirectoryOfNull(): void
    {
        $this->expectNotToPerformAssertions();
        new class extends AbstractDiscovery {
            public function __construct()
            {
                parent::__construct(null);
            }
        };
    }
}
