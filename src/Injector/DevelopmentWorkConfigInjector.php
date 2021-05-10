<?php

namespace Laminas\ComponentInstaller\Injector;

class DevelopmentWorkConfigInjector extends ApplicationConfigInjector
{
    /** @var string */
    protected $configFile = 'config/development.config.php';
}
