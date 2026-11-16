<?php

$configManager = new \Mezzio\ConfigManager\ConfigManager([
    \Application\ConfigProvider::class,
], 'data/cache/config.php');

return $configManager->getMergedConfig();
