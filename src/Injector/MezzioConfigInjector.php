<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\ConfigDiscovery\MezzioConfig as MezzioConfigDiscovery;

use function preg_quote;
use function sprintf;

class MezzioConfigInjector extends AbstractInjector
{
    use ConditionalDiscoveryTrait;

    const DEFAULT_CONFIG_FILE = 'config/config.php';

    /**
     * {@inheritDoc}
     */
    protected $allowedTypes = [
        self::TYPE_CONFIG_PROVIDER,
    ];

    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = self::DEFAULT_CONFIG_FILE;

    /**
     * Discovery class, for testing if this injector is valid for the given
     * configuration.
     *
     * @var string
     */
    protected $discoveryClass = MezzioConfigDiscovery::class;

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * Pattern is set in constructor due to PCRE quoting issues.
     *
     * @var string[]
     */
    protected $injectionPatterns = [
        self::TYPE_CONFIG_PROVIDER => [
            'pattern'     => '',
            'replacement' => "\$1\n    %s::class,",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Set in constructor due to PCRE quoting issues.
     *
     * @var string
     */
    protected $isRegisteredPattern = '';

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * @var string[]
     */
    protected $removalPatterns = [
        'pattern'     => '/^\s+%s::class,\s*$/m',
        'replacement' => '',
    ];

    /**
     * {@inheritDoc}
     *
     * Sets $isRegisteredPattern and pattern for $injectionPatterns to ensure
     * proper PCRE quoting.
     */
    public function __construct($projectRoot = '')
    {
        $this->isRegisteredPattern = '/new (?:'
            . preg_quote('\\')
            . '?'
            . preg_quote('Mezzio\ConfigManager\\')
            . ')?ConfigManager\(\s*(?:array\(|\[).*\s+%s::class/s';

        $this->injectionPatterns[self::TYPE_CONFIG_PROVIDER]['pattern'] = sprintf(
            '/(new (?:%s?%s)?ConfigManager\(\s*(?:array\(|\[)\s*)$/m',
            preg_quote('\\'),
            preg_quote('Mezzio\ConfigManager\\')
        );

        parent::__construct($projectRoot);
    }
}
