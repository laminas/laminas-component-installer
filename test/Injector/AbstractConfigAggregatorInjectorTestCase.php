<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use org\bovigo\vfs\vfsStream;

use function file_get_contents;
use function preg_replace;

abstract class AbstractConfigAggregatorInjectorTestCase extends AbstractInjectorTestCase
{
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
            'config-provider' => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, true],
            'component'       => [ConfigAggregatorInjector::TYPE_COMPONENT, false],
            'module'          => [ConfigAggregatorInjector::TYPE_MODULE, false],
        ];
    }

    public static function injectComponentProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import.config.php');
        $baseContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import-alt-indent.config.php');

        $baseContentsFqcnShortArray              = self::convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = self::convertToShortArraySyntax($baseContentsImportLongArray);
        $baseContentsImportShortArrayAltIndent   = self::convertToShortArraySyntax($baseContentsImportLongArrayAltIndent);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');
        $expectedContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import-alt-indent.config.php');

        $expectedContentsFqcnShortArray              = self::convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = self::convertToShortArraySyntax($expectedContentsImportLongArray);
        $expectedContentsImportShortArrayAltIndent   = self::convertToShortArraySyntax($expectedContentsImportLongArrayAltIndent);

        $injectOnlyFirstOccurrenceInitial  = file_get_contents(__DIR__ . '/TestAsset/mezzio-with-postprocessor.config.php');
        $injectOnlyFirstOccurrenceExpected = file_get_contents(__DIR__ . '/TestAsset/mezzio-with-postprocessor-post-injection.config.php');

        return [
            'fqcn-long-array'               => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'             => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'             => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'import-long-array-alt-indent'  => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportLongArrayAltIndent,    $expectedContentsImportLongArrayAltIndent],
            'fqcn-short-array'              => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array'            => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array'            => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportShortArray,            $expectedContentsImportShortArray],
            'import-short-array-alt-indent' => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportShortArrayAltIndent,   $expectedContentsImportShortArrayAltIndent],

            // see https://github.com/laminas/laminas-component-installer/issues/21
            'inject-only-first-occurence' => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $injectOnlyFirstOccurrenceInitial, $injectOnlyFirstOccurrenceExpected],
        ];
        // phpcs:enable
    }

    public static function packageAlreadyRegisteredProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');

        $fqcnShortArray              = self::convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = self::convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = self::convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [$fqcnLongArray,               ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'global-long-array'  => [$globallyQualifiedLongArray,  ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'import-long-array'  => [$importLongArray,             ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'fqcn-short-array'   => [$fqcnShortArray,              ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'global-short-array' => [$globallyQualifiedShortArray, ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'import-short-array' => [$importShortArray,            ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
        ];
        // phpcs:enable
    }

    public static function emptyConfiguration(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-import.config.php');
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
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');
        $baseContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import-alt-indent.config.php');

        $baseContentsFqcnShortArray              = self::convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = self::convertToShortArraySyntax($baseContentsImportLongArray);
        $baseContentsImportShortArrayAltIndent   = self::convertToShortArraySyntax($baseContentsImportLongArrayAltIndent);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import.config.php');
        $expectedContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import-alt-indent.config.php');

        $expectedContentsFqcnShortArray              = self::convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = self::convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = self::convertToShortArraySyntax($expectedContentsImportLongArray);
        $expectedContentsImportShortArrayAltIndent   = self::convertToShortArraySyntax($expectedContentsImportLongArrayAltIndent);

        return [
            'fqcn-long-array'               => [$baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'             => [$baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'             => [$baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'import-long-array-alt-indent'  => [$baseContentsImportLongArrayAltIndent,    $expectedContentsImportLongArrayAltIndent],
            'fqcn-short-array'              => [$baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array'            => [$baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array'            => [$baseContentsImportShortArray,            $expectedContentsImportShortArray],
            'import-short-array-alt-indent' => [$baseContentsImportShortArrayAltIndent,   $expectedContentsImportShortArrayAltIndent],
        ];
        // phpcs:enable
    }

    public function testProperlyDetectsExistingConfigProviderInConfigWithMixedRelativeAndGloballyQualifiedNames(): void
    {
        $contents = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-from-skeleton.config.php');
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->injector->isRegistered('Laminas\Validator\ConfigProvider'));
    }
}
