<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\Injector\DevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;

class DevelopmentConfigInjectorTest extends AbstractInjectorTestCase
{
    /** @var non-empty-string */
    protected $configFile = 'config/development.config.php.dist';

    /**
     * @var string
     * @psalm-var class-string<InjectorInterface>
     */
    protected $injectorClass = DevelopmentConfigInjector::class;

    /**
     * @var array
     * @psalm-var list<InjectorInterface::TYPE_*>
     */
    protected $injectorTypesAllowed = [
        InjectorInterface::TYPE_COMPONENT,
        InjectorInterface::TYPE_MODULE,
        InjectorInterface::TYPE_DEPENDENCY,
        InjectorInterface::TYPE_BEFORE_APPLICATION,
    ];

    public function allowedTypes(): array
    {
        return [
            'config-provider'            => [InjectorInterface::TYPE_CONFIG_PROVIDER, false],
            'component'                  => [InjectorInterface::TYPE_COMPONENT, true],
            'module'                     => [InjectorInterface::TYPE_MODULE, true],
            'dependency'                 => [InjectorInterface::TYPE_DEPENDENCY, true],
            'before-application-modules' => [InjectorInterface::TYPE_BEFORE_APPLICATION, true],
        ];
    }

    public function injectComponentProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'component-long-array'  => [InjectorInterface::TYPE_COMPONENT, $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);"],
            'component-short-array' => [InjectorInterface::TYPE_COMPONENT, $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];"],
            'module-long-array'     => [InjectorInterface::TYPE_MODULE,    $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);"],
            'module-short-array'    => [InjectorInterface::TYPE_MODULE,    $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];"],
        ];
        // phpcs:enable
    }

    public function packageAlreadyRegisteredProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'component-long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", InjectorInterface::TYPE_COMPONENT],
            'component-short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           InjectorInterface::TYPE_COMPONENT],
            'module-long-array'     => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);", InjectorInterface::TYPE_MODULE],
            'module-short-array'    => ['<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];",           InjectorInterface::TYPE_MODULE],
        ];
        // phpcs:enable
    }

    public function emptyConfiguration(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        // phpcs:enable

        return [
            'long-array'  => [$baseContentsLongArray],
            'short-array' => [$baseContentsShortArray],
        ];
    }

    public function packagePopulatedInConfiguration(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", $baseContentsLongArray],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           $baseContentsShortArray],
        ];
        // phpcs:enable
    }
}
