<?php
use Mezzio\ConfigManager\ConfigManager;

$configManager = new ConfigManager(array(
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
