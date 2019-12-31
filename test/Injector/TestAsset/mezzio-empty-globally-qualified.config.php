<?php

$configManager = new \Mezzio\ConfigManager\ConfigManager(array(
), 'data/cache/config.php');

return $configManager->getMergedConfig();
