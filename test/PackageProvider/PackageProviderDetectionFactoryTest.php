<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\PackageProvider;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\RootPackageRepository;
use Laminas\ComponentInstaller\PackageProvider\PackageProviderDetectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PackageProviderDetectionFactoryTest extends TestCase
{
    public function testUsesRootPackageRepositoryFromRootPackageIfExists(): void
    {
        $rootPackage = $this->createMock(RootPackage::class);
        $composer    = $this->createComposerMock($rootPackage);

        $repository = $this->createMock(RootPackageRepository::class);

        $rootPackage
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $factory = new PackageProviderDetectionFactory($composer);
        $event   = $this->createMock(PackageEvent::class);

        $packageProvider = $factory->detect($event, 'some/component');

        // Lets verify that this method is definitely called when we are calling PackageProviderDetection#whatProvides
        $repository
            ->expects(self::once())
            ->method('getPackages')
            ->willReturn([]);

        $packageProvider->whatProvides('some/component');
    }

    /**
     * @return Composer&MockObject
     */
    private function createComposerMock(
        ?RootPackage $package = null
    ): Composer {
        $composer = $this->createMock(Composer::class);

        $package ??= $this->createMock(RootPackage::class);

        $composer
            ->method('getPackage')
            ->willReturn($package);

        $installedRepository = $this->createMock(InstalledRepositoryInterface::class);
        $installedRepository
            ->method('getPackages')
            ->willReturn([]);

        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->method('getLocalRepository')
            ->willReturn($installedRepository);

        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        return $composer;
    }
}
