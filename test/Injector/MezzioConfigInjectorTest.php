<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComponentInstaller\Injector\MezzioConfigInjector;

use function file_get_contents;
use function preg_replace;

class MezzioConfigInjectorTest extends AbstractInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/config.php';

    /**
     * @var string
     * @psalm-var class-string<InjectorInterface>
     */
    protected $injectorClass = MezzioConfigInjector::class;

    /**
     * @var array
     * @psalm-var list<InjectorInterface::TYPE_*>
     */
    protected $injectorTypesAllowed = [
        InjectorInterface::TYPE_CONFIG_PROVIDER,
    ];

    public static function convertToShortArraySyntax(string $contents): string
    {
        return preg_replace('/array\(([^)]+)\)/s', '[$1]', $contents);
    }

    public static function allowedTypes(): array
    {
        return [
            'config-provider' => [InjectorInterface::TYPE_CONFIG_PROVIDER, true],
            'component'       => [InjectorInterface::TYPE_COMPONENT, false],
            'module'          => [InjectorInterface::TYPE_MODULE, false],
        ];
    }

    public static function injectComponentProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-import.config.php');

        $baseContentsFqcnShortArray              = self::convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = self::convertToShortArraySyntax($baseContentsImportLongArray);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-import.config.php');

        $expectedContentsFqcnShortArray              = self::convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = self::convertToShortArraySyntax($expectedContentsImportLongArray);

        return [
            'fqcn-long-array'    => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'  => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'  => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'fqcn-short-array'   => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array' => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array' => [InjectorInterface::TYPE_CONFIG_PROVIDER, $baseContentsImportShortArray,            $expectedContentsImportShortArray],
        ];
        // phpcs:enable
    }

    public static function packageAlreadyRegisteredProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-import.config.php');

        $fqcnShortArray              = self::convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = self::convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = self::convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [$fqcnLongArray,               InjectorInterface::TYPE_CONFIG_PROVIDER],
            'global-long-array'  => [$globallyQualifiedLongArray,  InjectorInterface::TYPE_CONFIG_PROVIDER],
            'import-long-array'  => [$importLongArray,             InjectorInterface::TYPE_CONFIG_PROVIDER],
            'fqcn-short-array'   => [$fqcnShortArray,              InjectorInterface::TYPE_CONFIG_PROVIDER],
            'global-short-array' => [$globallyQualifiedShortArray, InjectorInterface::TYPE_CONFIG_PROVIDER],
            'import-short-array' => [$importShortArray,            InjectorInterface::TYPE_CONFIG_PROVIDER],
        ];
        // phpcs:enable
    }

    public static function emptyConfiguration(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-empty-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-empty-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-empty-import.config.php');
        // phpcs:enable

        $fqcnShortArray              = self::convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = self::convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = self::convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [$fqcnLongArray],
            'global-long-array'  => [$globallyQualifiedLongArray],
            'import-long-array'  => [$importLongArray],
            'fqcn-short-array'   => [$fqcnShortArray],
            'global-short-array' => [$globallyQualifiedShortArray],
            'import-short-array' => [$importShortArray],
        ];
    }

    public static function packagePopulatedInConfiguration(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-populated-import.config.php');

        $baseContentsFqcnShortArray              = self::convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = self::convertToShortArraySyntax($baseContentsImportLongArray);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/legacy-mezzio-application-import.config.php');

        $expectedContentsFqcnShortArray              = self::convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = self::convertToShortArraySyntax($expectedContentsImportLongArray);

        return [
            'fqcn-long-array'    => [$baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'  => [$baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'  => [$baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'fqcn-short-array'   => [$baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array' => [$baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array' => [$baseContentsImportShortArray,            $expectedContentsImportShortArray],
        ];
        // phpcs:enable
    }
}
