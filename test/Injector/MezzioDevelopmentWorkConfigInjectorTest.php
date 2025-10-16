<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\MezzioDevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\MezzioDevelopmentWorkConfigInjector;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MezzioDevelopmentConfigInjector::class)]
final class MezzioDevelopmentWorkConfigInjectorTest extends AbstractConfigAggregatorInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/development.config.php';
    /** @var class-string<InjectorInterface> */
    protected $injectorClass = MezzioDevelopmentWorkConfigInjector::class;
}
