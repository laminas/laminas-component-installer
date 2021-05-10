<?php

namespace Laminas\ComponentInstaller\Injector;

class DevelopmentConfigInjector extends ApplicationConfigInjector
{
    /** @var string */
    protected $configFile = 'config/development.config.php.dist';
}
