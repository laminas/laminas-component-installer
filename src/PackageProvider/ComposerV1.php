<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\DependencyResolver\Pool;

use function array_values;

/**
 * @internal
 */
final class ComposerV1 implements PackageProviderDetectionInterface
{
    private Pool $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function whatProvides(string $packageName): array
    {
        return array_values($this->pool->whatProvides($packageName));
    }
}
