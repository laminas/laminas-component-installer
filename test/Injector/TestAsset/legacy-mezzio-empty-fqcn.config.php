<?php

$configManager = new Mezzio\ConfigManager\ConfigManager([
], 'data/cache/config.php');

return $configManager->getMergedConfig();
