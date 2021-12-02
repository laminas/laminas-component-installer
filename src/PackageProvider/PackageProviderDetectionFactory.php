<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface as ComposerRepositoryInterface;
use Composer\Repository\RootPackageRepository;

use function version_compare;

final class PackageProviderDetectionFactory
{
    /** @var Composer */
    private $composer;
    /** @var null|RootPackageRepository */
    private $packageRepository;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        if (false === self::isComposerV1()) {
            $this->packageRepository = new RootPackageRepository($composer->getPackage());
        }
    }

    public static function create(Composer $composer): self
    {
        return new self($composer);
    }

    public static function isComposerV1(): bool
    {
        return version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0.0', '<') === true;
    }

    public function detect(PackageEvent $event, string $packageName): PackageProviderDetectionInterface
    {
        if (self::isComposerV1()) {
            return new ComposerV1($event->getPool());
        }

        $installedRepo = new InstalledRepository($this->prepareRepositoriesForInstalledRepository());
        $defaultRepos  = new CompositeRepository(RepositoryFactory::defaultRepos(new NullIO()));

        if (
            ($match = $defaultRepos->findPackage($packageName, '*'))
            && false === $installedRepo->hasPackage($match)
        ) {
            $installedRepo->addRepository(new InstalledArrayRepository([clone $match]));
        }

        return new ComposerV2($installedRepo);
    }

    /** @psalm-return ComposerRepositoryInterface[] */
    private function prepareRepositoriesForInstalledRepository(): array
    {
        $platformOverrides = $this->composer->getConfig()->get('platform') ?? [];

        if (null === $this->packageRepository) {
            return [
                $this->composer->getRepositoryManager()->getLocalRepository(),
                new PlatformRepository([], $platformOverrides),
            ];
        }

        return [
            $this->packageRepository,
            $this->composer->getRepositoryManager()->getLocalRepository(),
            new PlatformRepository([], $platformOverrides),
        ];
    }
}
