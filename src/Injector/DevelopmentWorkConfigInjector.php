<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller\Injector;

class DevelopmentWorkConfigInjector extends ApplicationConfigInjector
{
    /** @var string */
    protected $configFile = 'config/development.config.php';
}
