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

    protected $allowedTypes = [
        self::TYPE_CONFIG_PROVIDER,
    ];

    protected $configFile = self::DEFAULT_CONFIG_FILE;

    /**
     * Discovery class, for testing if this injector is valid for the given
     * configuration.
     *
     * @var string
     * @psalm-var non-empty-string
     */
    protected $discoveryClass = ConfigAggregatorDiscovery::class;

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * Pattern is set in constructor due to PCRE quoting issues.
     * @var array<int,array<string,string>>
     * @psalm-var array<InjectorInterface::TYPE_*,array{pattern:non-empty-string,replacement:non-empty-string}>
     */
    protected $injectionPatterns = [];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Set in constructor due to PCRE quoting issues.
     */
    protected $isRegisteredPattern = 'overridden-by-constructor';

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

        $pattern = sprintf(
            "/(new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)\s*)(?:\r|\n|\r\n)(\s*)/",
            preg_quote('\\'),
            preg_quote('Laminas\ConfigAggregator\\')
        );
        assert($pattern !== '');
        $this->injectionPatterns[self::TYPE_CONFIG_PROVIDER] = [
            'pattern' => $pattern,
            'replacement' => "\$1\n\$2%s::class,\n\$2",
        ];

        parent::__construct($projectRoot);
    }
}
