<?php

$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator(array(
    \Application\ConfigProvider::class,
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
