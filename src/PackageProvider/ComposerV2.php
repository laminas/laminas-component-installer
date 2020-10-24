<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Repository\InstalledRepository;
use Composer\Repository\InstalledRepositoryInterface;

final class ComposerV2 implements PackageProviderDetectionInterface
{
    /**
     * @var InstalledRepositoryInterface
     */
    private $installedRepository;

    public function __construct(InstalledRepository $installedRepository)
    {
        $this->installedRepository = $installedRepository;
    }

    public function whatProvides(string $packageName): array
    {
        return $this->installedRepository->findPackagesWithReplacersAndProviders($packageName);
    }
}
