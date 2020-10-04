<?php
declare(strict_types=1);

namespace Laminas\ComponentInstaller\PackageProvider;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\Plugin\PluginInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RootPackageRepository;
use function version_compare;

final class PackageProviderDetectionFactory
{
    /**
     * @var Composer
     */
    private $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
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

        $platformOverrides = $this->composer->getConfig()->get('platform') ?? [];

        $installedRepo = new InstalledRepository([
            new RootPackageRepository($this->composer->getPackage()),
            $this->composer->getRepositoryManager()->getLocalRepository(),
            new PlatformRepository([], $platformOverrides),
        ]);

        $defaultRepos = new CompositeRepository(RepositoryFactory::defaultRepos());
        if ($match = $defaultRepos->findPackage($packageName, '')) {
            $installedRepo->addRepository(new InstalledArrayRepository([clone $match]));
        }

        return new ComposerV2($installedRepo);
    }
}
