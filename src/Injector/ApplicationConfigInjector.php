<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

class ApplicationConfigInjector extends AbstractInjector
{
    /** @var string */
    protected $configFile = 'config/application.config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var array
     * @psalm-var array<
     *     InjectorInterface::TYPE_*,
     *     array{pattern: non-empty-string, replacement: string}
     * >
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT          => [
            'pattern'     => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\$2\n\$1    '%s',",
        ],
        self::TYPE_MODULE             => [
            'pattern'     => "/('modules'\s*\=\>\s*(?:array\s*\(|\[).*?)\n(\s+)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
        self::TYPE_DEPENDENCY         => [
            'pattern'     => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[)[^)\]]*\'%s\')/m',
            'replacement' => "\$1\$2,\n\$1    '%s'",
        ],
        self::TYPE_BEFORE_APPLICATION => [
            'pattern'     => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[)[^)\]]*)(\'%s\')/m',
            'replacement' => "\$1\$2'%s',\n$1    \$3",
        ],
    ];

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    protected $isRegisteredPattern = '/\'modules\'\s*\=\>\s*(?:array\(|\[)[^)\]]*\'%s\'/s';

    /**
     * @var array
     * @psalm-var array{pattern: non-empty-string, replacement: string}
     */
    protected $removalPatterns = [
        'pattern'     => '/^\s+\'%s\',\s*$/m',
        'replacement' => '',
    ];
}
