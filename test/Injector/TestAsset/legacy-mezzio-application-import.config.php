<?php
use Mezzio\ConfigManager\ConfigManager;

$configManager = new ConfigManager([
    Application\ConfigProvider::class,
], 'data/cache/config.php');

return $configManager->getMergedConfig();
