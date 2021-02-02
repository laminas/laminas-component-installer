<?php
declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\TestAsset;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;

/**
 * Class to ensure we do not have to mock each of these methods in every unit test.
 * PHPUnit will create return values based on the native typehint.
 */
class NativeTypehintedInstallationManager extends InstallationManager
{
    public function getInstallPath(PackageInterface $package): string
    {
        return parent::getInstallPath($package);
    }
}
