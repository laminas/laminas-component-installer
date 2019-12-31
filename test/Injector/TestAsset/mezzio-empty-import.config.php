<?php
use Mezzio\ConfigManager\ConfigManager;

$configManager = new ConfigManager(array(
), 'data/cache/config.php');

return $configManager->getMergedConfig();
