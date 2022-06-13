<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Composer;
use Composer\Installer\PackageEvent;

final class LazyPackageProviderDetectionFactory implements PackageProviderDetectionFactoryInterface
{
    private ?PackageProviderDetectionFactoryInterface $packageProviderDetectionFactory = null;

    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function detect(PackageEvent $event, string $packageName): PackageProviderDetectionInterface
    {
        if ($this->packageProviderDetectionFactory === null) {
            $this->packageProviderDetectionFactory = PackageProviderDetectionFactory::create($this->composer);
        }

        return $this->packageProviderDetectionFactory->detect($event, $packageName);
    }
}
