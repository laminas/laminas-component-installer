<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\ConfigDiscovery;

/**
 * @internal
 */
final readonly class DevelopmentWorkConfig implements DiscoveryInterface
{
    private const string CONFIG_FILE = 'config/development.config.php';

    private DiscoveryInterface $applicationDiscovery;

    public function __construct(string $projectDirectory = '')
    {
        $this->applicationDiscovery = new ApplicationConfig($projectDirectory, self::CONFIG_FILE);
    }

    public function locate(): bool
    {
        return $this->applicationDiscovery->locate();
    }
}
