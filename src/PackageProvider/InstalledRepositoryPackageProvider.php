<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Repository\InstalledRepository;

use function array_values;

/**
 * @internal
 */
final class InstalledRepositoryPackageProvider implements PackageProviderDetectionInterface
{
    public function __construct(private readonly InstalledRepository $installedRepository)
    {
    }

    public function whatProvides(string $packageName): array
    {
        return array_values($this->installedRepository->findPackagesWithReplacersAndProviders($packageName));
    }
}
