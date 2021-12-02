<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

use Laminas\ComponentInstaller\ConfigDiscovery\MezzioConfig as MezzioConfigDiscovery;

use function assert;
use function preg_quote;
use function sprintf;

class MezzioConfigInjector extends AbstractInjector
{
    use ConditionalDiscoveryTrait;

    public const DEFAULT_CONFIG_FILE = 'config/config.php';

    /**
     * {@inheritDoc}
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
     */
    protected $discoveryClass = MezzioConfigDiscovery::class;

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
     * @var non-empty-string
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
        $this->isRegisteredPattern = '/new (?:'
            . preg_quote('\\')
            . '?'
            . preg_quote('Mezzio\ConfigManager\\')
            . ')?ConfigManager\(\s*(?:array\(|\[).*\s+%s::class/s';

        $pattern = sprintf(
            '/(new (?:%s?%s)?ConfigManager\(\s*(?:array\(|\[)\s*)$/m',
            preg_quote('\\'),
            preg_quote('Mezzio\ConfigManager\\')
        );
        assert($pattern !== '');
        $this->injectionPatterns[self::TYPE_CONFIG_PROVIDER] = [
            'pattern'     => $pattern,
            'replacement' => "\$1\n    %s::class,",
        ];

        parent::__construct($projectRoot);
    }
}
