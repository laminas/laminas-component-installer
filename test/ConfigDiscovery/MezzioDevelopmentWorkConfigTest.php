<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;
use Laminas\ComponentInstaller\ConfigDiscovery\MezzioDevelopmentWorkConfig;

final class MezzioDevelopmentWorkConfigTest extends AbstractConfigAggregatorTestCase
{
    /** @var class-string<DiscoveryInterface> */
    protected string $discoveryClass = MezzioDevelopmentWorkConfig::class;
    protected string $configFile     = 'config/development.config.php';
}
