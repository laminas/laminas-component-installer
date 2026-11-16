<?php
use Mezzio\ConfigManager\ConfigManager;

$configManager = new ConfigManager([
], 'data/cache/config.php');

return $configManager->getMergedConfig();
