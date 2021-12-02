<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\ConfigDiscovery\ConfigAggregator as ConfigAggregatorDiscovery;

use function assert;
use function preg_quote;
use function sprintf;

class ConfigAggregatorInjector extends AbstractInjector
{
    use ConditionalDiscoveryTrait;

    public const DEFAULT_CONFIG_FILE = 'config/config.php';

    /**
     * @var array
     * @psalm-var list<InjectorInterface::TYPE_*>
     */
    protected $allowedTypes = [
        self::TYPE_CONFIG_PROVIDER,
    ];

    /** @var string */
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
     *
     * @var array
     * @psalm-var array<
     *     InjectorInterface::TYPE_*,
     *     array{pattern: non-empty-string, replacement: string}
     * >
     */
    protected $injectionPatterns = [];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Set in constructor due to PCRE quoting issues.
     *
     * @var string
     * @psalm-var non-empty-string
     */
    protected $isRegisteredPattern = 'overridden-by-constructor';

    /**
     * @var array
     * @psalm-var array{pattern: non-empty-string, replacement: string}
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
        $ns                        = preg_quote('\\');
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
            'pattern'     => $pattern,
            'replacement' => "\$1\n\$2%s::class,\n\$2",
        ];

        parent::__construct($projectRoot);
    }
}
