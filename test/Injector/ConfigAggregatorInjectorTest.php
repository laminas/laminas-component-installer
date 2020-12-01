<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use org\bovigo\vfs\vfsStream;

use function file_get_contents;
use function preg_replace;

class ConfigAggregatorInjectorTest extends AbstractInjectorTestCase
{
    /** @var string */
    protected $configFile = 'config/config.php';

    /** @var string */
    protected $injectorClass = ConfigAggregatorInjector::class;

    /** @var int[] */
    protected $injectorTypesAllowed = [
        ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER,
    ];

    public function convertToShortArraySyntax(string $contents): string
    {
        return preg_replace('/array\(([^)]+)\)/s', '[$1]', $contents);
    }

    /**
     * @psalm-return array<string, array{0: int, 1: bool}>
     */
    public function allowedTypes(): array
    {
        return [
            'config-provider' => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, true],
            'component'       => [ConfigAggregatorInjector::TYPE_COMPONENT, false],
            'module'          => [ConfigAggregatorInjector::TYPE_MODULE, false],
        ];
    }

    /**
     * @psalm-return array<string, array{0: int, 1: string, 2: string}>
     */
    public function injectComponentProvider(): array
    {
        // @codingStandardsIgnoreStart
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import.config.php');
        $baseContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import-alt-indent.config.php');

        $baseContentsFqcnShortArray              = $this->convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = $this->convertToShortArraySyntax($baseContentsImportLongArray);
        $baseContentsImportShortArrayAltIndent   = $this->convertToShortArraySyntax($baseContentsImportLongArrayAltIndent);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');
        $expectedContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import-alt-indent.config.php');

        $expectedContentsFqcnShortArray              = $this->convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = $this->convertToShortArraySyntax($expectedContentsImportLongArray);
        $expectedContentsImportShortArrayAltIndent   = $this->convertToShortArraySyntax($expectedContentsImportLongArrayAltIndent);

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
            'inject-only-first-occurence'   => [ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER, $injectOnlyFirstOccurrenceInitial, $injectOnlyFirstOccurrenceExpected],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @psalm-return array<string, array{0: string, 1: int}>
     */
    public function packageAlreadyRegisteredProvider(): array
    {
        // @codingStandardsIgnoreStart
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');

        $fqcnShortArray              = $this->convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = $this->convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = $this->convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'           => [$fqcnLongArray,               ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'global-long-array'         => [$globallyQualifiedLongArray,  ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'import-long-array'         => [$importLongArray,             ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'fqcn-short-array'          => [$fqcnShortArray,              ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'global-short-array'        => [$globallyQualifiedShortArray, ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
            'import-short-array'        => [$importShortArray,            ConfigAggregatorInjector::TYPE_CONFIG_PROVIDER],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @psalm-return array<string, array{0: string}>
     */
    public function emptyConfiguration(): array
    {
        // @codingStandardsIgnoreStart
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-empty-import.config.php');
        // @codingStandardsIgnoreEnd

        $fqcnShortArray              = $this->convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = $this->convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = $this->convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [$fqcnLongArray],
            'global-long-array'  => [$globallyQualifiedLongArray],
            'import-long-array'  => [$importLongArray],
            'fqcn-short-array'   => [$fqcnShortArray],
            'global-short-array' => [$globallyQualifiedShortArray],
            'import-short-array' => [$importShortArray],
        ];
    }

    /**
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function packagePopulatedInConfiguration(): array
    {
        // @codingStandardsIgnoreStart
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import.config.php');
        $baseContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-populated-import-alt-indent.config.php');

        $baseContentsFqcnShortArray              = $this->convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = $this->convertToShortArraySyntax($baseContentsImportLongArray);
        $baseContentsImportShortArrayAltIndent   = $this->convertToShortArraySyntax($baseContentsImportLongArrayAltIndent);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import.config.php');
        $expectedContentsImportLongArrayAltIndent   = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-import-alt-indent.config.php');

        $expectedContentsFqcnShortArray              = $this->convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = $this->convertToShortArraySyntax($expectedContentsImportLongArray);
        $expectedContentsImportShortArrayAltIndent   = $this->convertToShortArraySyntax($expectedContentsImportLongArrayAltIndent);

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
        // @codingStandardsIgnoreEnd
    }

    public function testProperlyDetectsExistingConfigProviderInConfigWithMixedRelativeAndGloballyQualifiedNames(): void
    {
        $contents = file_get_contents(__DIR__ . '/TestAsset/mezzio-application-from-skeleton.config.php');
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->injector->isRegistered('Laminas\Validator\ConfigProvider'));
    }
}
