<?php
declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\TestAsset;

use Composer\Package\RootPackageInterface;

interface NativeTypehintedRootPackageInterface extends RootPackageInterface
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
