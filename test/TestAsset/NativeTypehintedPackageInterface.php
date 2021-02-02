<?php
declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\TestAsset;

use Composer\Package\PackageInterface;

/**
 * Interface to ensure we do not have to mock each of these methods in every unit test.
 * PHPUnit will create return values based on the native typehint.
 */
interface NativeTypehintedPackageInterface extends PackageInterface
{
    public function getAutoload(): array;
    public function getName(): string;
    public function getExtra(): array;
    public function getProvides(): array;
    public function getReplaces(): array;
    public function getRequires(): array;
    public function getDevRequires(): array;
    public function getDevAutoload(): array;
}
