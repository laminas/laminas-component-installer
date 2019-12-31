<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\ConfigDiscovery\ConfigAggregator as ConfigAggregatorDiscovery;

use function preg_quote;
use function sprintf;

class ConfigAggregatorInjector extends AbstractInjector
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
    protected $discoveryClass = ConfigAggregatorDiscovery::class;

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
            'replacement' => "\$1\n\$2%s::class,\n\$2",
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
        $ns = preg_quote('\\');
        $this->isRegisteredPattern = '/new (?:'
            . $ns
            . '?'
            . preg_quote('Laminas\ConfigAggregator\\')
            . ')?ConfigAggregator\(\s*(?:array\(|\[).*\s+'
            . $ns
            . '?%s::class/s';

        $this->injectionPatterns[self::TYPE_CONFIG_PROVIDER]['pattern'] = sprintf(
            "/(new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)\s*)(?:\r|\n|\r\n)(\s*)/",
            preg_quote('\\'),
            preg_quote('Laminas\ConfigAggregator\\')
        );

        parent::__construct($projectRoot);
    }
}
