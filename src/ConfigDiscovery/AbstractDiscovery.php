<?php

namespace Laminas\ComponentInstaller\ConfigDiscovery;

use function file_get_contents;
use function is_dir;
use function is_file;
use function preg_match;
use function sprintf;

abstract class AbstractDiscovery implements DiscoveryInterface
{
    /**
     * Configuration file to look for.
     *
     * Implementations MUST overwite this.
     *
     * @var string
     */
    protected $configFile;

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * Implementations MUST overwrite this.
     *
     * @var string
     */
    protected $expected;

    /**
     * Constructor
     *
     * Optionally specify project directory; $configFile will be relative to
     * this value.
     *
     * @param string $projectDirectory
     */
    public function __construct($projectDirectory = '')
    {
        if ('' !== (string) $projectDirectory && is_dir($projectDirectory)) {
            $this->configFile = sprintf(
                '%s/%s',
                $projectDirectory,
                $this->configFile
            );
        }
    }

    /**
     * Determine if the configuration file exists and contains modules.
     *
     * @return bool
     */
    public function locate()
    {
        if (! is_file($this->configFile)) {
            return false;
        }

        $config = file_get_contents($this->configFile);
        return 1 === preg_match($this->expected, $config);
    }
}
