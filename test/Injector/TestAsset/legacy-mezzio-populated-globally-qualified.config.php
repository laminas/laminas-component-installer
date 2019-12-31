<?php

$configManager = new \Mezzio\ConfigManager\ConfigManager(array(
    \Foo\Bar::class,
    \Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
