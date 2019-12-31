<?php

$configManager = new \Mezzio\ConfigManager\ConfigManager(array(
    \Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
