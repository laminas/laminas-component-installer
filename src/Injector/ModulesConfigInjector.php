<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

class ModulesConfigInjector extends AbstractInjector
{
    /** @var string */
    protected $configFile = 'config/modules.config.php';

    /**
     * @var array
     * @psalm-var array<
     *     InjectorInterface::TYPE_*,
     *     array{pattern: non-empty-string, replacement: string}
     * >
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT          => [
            'pattern'     => '/^(return\s+(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\n    '%s',",
        ],
        self::TYPE_MODULE             => [
            'pattern'     => "/(return\s+(?:array\s*\(|\[).*?)\n(\s*)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
        self::TYPE_DEPENDENCY         => [
            'pattern'     => '/^(return\s+(?:array\s*\(|\[)[^)\]]*\'%s\')/m',
            'replacement' => "\$1,\n    '%s'",
        ],
        self::TYPE_BEFORE_APPLICATION => [
            'pattern'     => '/^(return\s+(?:array\s*\(|\[)[^)\]]*)(\'%s\')/m',
            'replacement' => "\$1'%s',\n    \$2",
        ],
    ];

    /** @var non-empty-string */
    protected $isRegisteredPattern = '/return\s+(?:array\(|\[)[^)\]]*\'%s\'/s';

    /**
     * @var array
     * @psalm-var array{pattern: non-empty-string, replacement: string}
     */
    protected $removalPatterns = [
        'pattern'     => '/^\s+\'%s\',\s*$/m',
        'replacement' => '',
    ];
}
