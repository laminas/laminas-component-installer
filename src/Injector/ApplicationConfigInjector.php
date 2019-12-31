<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

class ApplicationConfigInjector extends AbstractInjector
{
    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/application.config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var string[]
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT => [
            'pattern' => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\$2\n\$1    '%s',",
        ],
        self::TYPE_MODULE => [
            'pattern' => "/('modules'\s*\=\>\s*(?:array\s*\(|\[).*?)\n(\s+)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
        self::TYPE_DEPENDENCY => [
            'pattern' => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[)[^)\]]*\'%s\')/m',
            'replacement' => "\$1\$2,\n\$1    '%s'",
        ],
        self::TYPE_BEFORE_APPLICATION => [
            'pattern' => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[)[^)\]]*)(\'%s\')/m',
            'replacement' => "\$1\$2'%s',\n$1    \$3",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * @var string
     */
    protected $isRegisteredPattern = '/\'modules\'\s*\=\>\s*(?:array\(|\[)[^)\]]*\'%s\'/s';

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
