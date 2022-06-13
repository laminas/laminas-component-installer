<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Package\PackageInterface;

interface PackageProviderDetectionInterface
{
    /**
     * @return list<PackageInterface>
     */
    public function whatProvides(string $packageName): array;
}
