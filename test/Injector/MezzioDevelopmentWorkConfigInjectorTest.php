<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\MezzioDevelopmentWorkConfigInjector;

/**
 * @covers \Laminas\ComponentInstaller\Injector\MezzioDevelopmentConfigInjector
 */
final class MezzioDevelopmentWorkConfigInjectorTest extends AbstractConfigAggregatorInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/development.config.php';
    /** @var class-string<InjectorInterface> */
    protected $injectorClass = MezzioDevelopmentWorkConfigInjector::class;
}
