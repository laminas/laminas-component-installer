<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

class ModulesConfigInjector extends AbstractInjector
{
    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/modules.config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var array[]
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\n    '%s',",
        ],
        self::TYPE_MODULE => [
            'pattern' => "/(return\s+(?:array\s*\(|\[).*?)\n(\s*)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
        self::TYPE_DEPENDENCY => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[)[^)\]]*\'%s\')/m',
            'replacement' => "\$1,\n    '%s'",
        ],
        self::TYPE_BEFORE_APPLICATION => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[)[^)\]]*)(\'%s\')/m',
            'replacement' => "\$1'%s',\n    \$2",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * @var string
     */
    protected $isRegisteredPattern = '/return\s+(?:array\(|\[)[^)\]]*\'%s\'/s';

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * @var string[]
     */
    protected $removalPatterns = [
        'pattern' => '/^\s+\'%s\',\s*$/m',
        'replacement' => '',
    ];
}
