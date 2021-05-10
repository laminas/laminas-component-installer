<?php

namespace Laminas\ComponentInstaller\ConfigDiscovery;

use function preg_quote;
use function sprintf;

class ConfigAggregator extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * Pattern is set in constructor to ensure PCRE quoting is correct.
     *
     * @var string
     */
    protected $expected = '';

    /**
     * @param string $projectDirectory
     */
    public function __construct($projectDirectory = '')
    {
        $this->expected = sprintf(
            '/new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)/s',
            preg_quote('\\'),
            preg_quote('Laminas\ConfigAggregator\\')
        );

        parent::__construct($projectDirectory);
    }
}
