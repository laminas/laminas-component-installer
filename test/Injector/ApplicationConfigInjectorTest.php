<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\ApplicationConfigInjector;

class ApplicationConfigInjectorTest extends AbstractInjectorTestCase
{
    protected $configFile = 'config/application.config.php';

    protected $injectorClass = ApplicationConfigInjector::class;

    protected $injectorTypesAllowed = [
        ApplicationConfigInjector::TYPE_COMPONENT,
        ApplicationConfigInjector::TYPE_MODULE,
        ApplicationConfigInjector::TYPE_DEPENDENCY,
        ApplicationConfigInjector::TYPE_BEFORE_APPLICATION,
    ];

    public function allowedTypes(): array
    {
        return [
            'config-provider'            => [ApplicationConfigInjector::TYPE_CONFIG_PROVIDER, false],
            'component'                  => [ApplicationConfigInjector::TYPE_COMPONENT, true],
            'module'                     => [ApplicationConfigInjector::TYPE_MODULE, true],
            'dependency'                 => [ApplicationConfigInjector::TYPE_DEPENDENCY, true],
            'before-application-modules' => [ApplicationConfigInjector::TYPE_BEFORE_APPLICATION, true],
        ];
    }

    public function injectComponentProvider(): array
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'component-long-array'  => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);"],
            'component-short-array' => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];"],
            'module-long-array'     => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);"],
            'module-short-array'    => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];"],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @psalm-return array<string, array{
     *     0: string,
     *     1: int
     * }>
     */
    public function packageAlreadyRegisteredProvider(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'component-long-array'      => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", ApplicationConfigInjector::TYPE_COMPONENT],
            'component-short-array'     => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           ApplicationConfigInjector::TYPE_COMPONENT],
            'component-escaped-slashes' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\\\\Bar',\n        'Application',\n    ]\n];",           ApplicationConfigInjector::TYPE_COMPONENT],
            'module-long-array'         => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);", ApplicationConfigInjector::TYPE_MODULE],
            'module-short-array'        => ['<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];",           ApplicationConfigInjector::TYPE_MODULE],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @psalm-return array<string, array{0: string}>
     */
    public function emptyConfiguration(): array
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        // @codingStandardsIgnoreEnd

        return [
            'long-array'  => [$baseContentsLongArray],
            'short-array' => [$baseContentsShortArray],
        ];
    }

    /**
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function packagePopulatedInConfiguration(): array
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", $baseContentsLongArray],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
