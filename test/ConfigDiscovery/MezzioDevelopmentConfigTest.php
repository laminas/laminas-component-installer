<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;
use Laminas\ComponentInstaller\ConfigDiscovery\MezzioDevelopmentConfig;

final class MezzioDevelopmentConfigTest extends AbstractConfigAggregatorTestCase
{
    protected string $configFile = 'config/development.config.php.dist';
    /** @var class-string<DiscoveryInterface> */
    protected string $discoveryClass = MezzioDevelopmentConfig::class;
}
