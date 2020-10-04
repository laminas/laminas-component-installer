<?php
declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\DependencyResolver\Pool;
use Composer\Package\PackageInterface;

final class ComposerV1 implements PackageProviderDetectionInterface
{
    /**
     * @var Pool
     */
    private $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function whatProvides(string $packageName): array
    {
        return $this->pool->whatProvides($packageName);
    }
}
