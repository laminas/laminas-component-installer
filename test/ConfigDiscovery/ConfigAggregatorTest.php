<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\ConfigAggregator;
use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;

final class ConfigAggregatorTest extends AbstractConfigAggregatorTestCase
{
    /** @var class-string<DiscoveryInterface> */
    protected string $discoveryClass = ConfigAggregator::class;
    protected string $configFile     = 'config/config.php';
}
