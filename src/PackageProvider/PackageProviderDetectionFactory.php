<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface as ComposerRepositoryInterface;
use Composer\Repository\RootPackageRepository;

/**
 * @internal
 */
final class PackageProviderDetectionFactory implements PackageProviderDetectionFactoryInterface
{
    private Composer $composer;
    private RootPackageRepository $packageRepository;

    public function __construct(Composer $composer)
    {
        $this->composer          = $composer;
        $this->packageRepository = new RootPackageRepository($composer->getPackage());
    }

    public static function create(Composer $composer): self
    {
        return new self($composer);
    }

    public function detect(PackageEvent $event, string $packageName): PackageProviderDetectionInterface
    {
        $installedRepo = new InstalledRepository($this->prepareRepositoriesForInstalledRepository());
        $defaultRepos  = new CompositeRepository(RepositoryFactory::defaultRepos(new NullIO()));

        if (
            ($match = $defaultRepos->findPackage($packageName, '*'))
            && false === $installedRepo->hasPackage($match)
        ) {
            $installedRepo->addRepository(new InstalledArrayRepository([clone $match]));
        }

        return new InstalledRepositoryPackageProvider($installedRepo);
    }

    /**
     * @return list<ComposerRepositoryInterface>
     */
    private function prepareRepositoriesForInstalledRepository(): array
    {
        /** @var array<string,string|false> $platformOverrides */
        $platformOverrides = $this->composer->getConfig()->get('platform') ?? [];

        return [
            $this->packageRepository,
            $this->composer->getRepositoryManager()->getLocalRepository(),
            new PlatformRepository([], $platformOverrides),
        ];
    }
}
