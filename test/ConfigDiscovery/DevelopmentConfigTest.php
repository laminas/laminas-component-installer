<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\ConfigDiscovery;

use Laminas\ComponentInstaller\ConfigDiscovery\DevelopmentConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DevelopmentConfigTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $configDir;

    /** @var DevelopmentConfig */
    private $locator;

    #[Override]
    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new DevelopmentConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate(): void
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents(): void
    {
        vfsStream::newFile('config/development.config.php.dist')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    /**
     * @psalm-return array<string, array{0: string}>
     */
    public static function validDevelopmentConfigContents(): array
    {
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n    )\n);"],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n    ]\n];"],
        ];
    }

    #[DataProvider('validDevelopmentConfigContents')]
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent(string $contents): void
    {
        vfsStream::newFile('config/development.config.php.dist')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
