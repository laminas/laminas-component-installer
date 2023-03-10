<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\MezzioDevelopmentConfigInjector;

/**
 * @covers \Laminas\ComponentInstaller\Injector\MezzioDevelopmentConfigInjector
 */
final class MezzioDevelopmentConfigInjectorTest extends AbstractConfigAggregatorInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/development.config.php.dist';
    /** @var class-string<InjectorInterface> */
    protected $injectorClass = MezzioDevelopmentConfigInjector::class;
}
