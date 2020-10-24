<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Package\PackageInterface;

interface PackageProviderDetectionInterface
{
    /**
     * @return PackageInterface[]
     */
    public function whatProvides(string $packageName): array;
}
