<?php
use Mezzio\ConfigManager\ConfigManager;

$configManager = new ConfigManager([
    \Foo\Bar::class,
    Application\ConfigProvider::class,
], 'data/cache/config.php');

return $configManager->getMergedConfig();
