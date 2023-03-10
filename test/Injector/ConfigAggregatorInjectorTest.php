<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;

final class ConfigAggregatorInjectorTest extends AbstractConfigAggregatorInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/config.php';
    /**
     * @var string
     * @psalm-var class-string<InjectorInterface>
     */
    protected $injectorClass = ConfigAggregatorInjector::class;
}
