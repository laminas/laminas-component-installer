<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\ConfigDiscovery\ConfigAggregator as ConfigAggregatorDiscovery;
use Laminas\ComponentInstaller\ConfigDiscovery\DiscoveryInterface;

use function assert;
use function preg_quote;
use function sprintf;

/**
 * @internal
 */
final class ConfigAggregatorInjector extends AbstractInjector
{
    use ConditionalDiscoveryTrait;

    public const DEFAULT_CONFIG_FILE = 'config/config.php';

    /** @var list<InjectorInterface::TYPE_*> */
    protected array $allowedTypes = [
        self::TYPE_CONFIG_PROVIDER,
    ];

    /** @var non-empty-string */
    protected string $configFile = self::DEFAULT_CONFIG_FILE;

    /**
     * Discovery class, for testing if this injector is valid for the given
     * configuration.
     *
     * @var class-string<DiscoveryInterface>
     */
    protected string $discoveryClass = ConfigAggregatorDiscovery::class;

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * Pattern is set in constructor due to PCRE quoting issues.
     *
     * @var array<
     *     InjectorInterface::TYPE_*,
     *     array{pattern: non-empty-string, replacement: string}
     * >
     */
    protected array $injectionPatterns = [];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Set in constructor due to PCRE quoting issues.
     *
     * @var non-empty-string
     */
    protected string $isRegisteredPattern = 'overridden-by-constructor';

    /** @var array{pattern: non-empty-string, replacement: string} */
    protected array $removalPatterns = [
        'pattern'     => '/^\s+%s::class,\s*$/m',
        'replacement' => '',
    ];

    /**
     * {@inheritDoc}
     *
     * Sets $isRegisteredPattern and pattern for $injectionPatterns to ensure
     * proper PCRE quoting.
     */
    public function __construct(string $projectRoot = '')
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

    protected function getDefaultConfigFile(): string
    {
        return self::DEFAULT_CONFIG_FILE;
    }

    protected function getDiscoveryClass(): string
    {
        return $this->discoveryClass;
    }
}
