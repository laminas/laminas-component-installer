<?php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator(
    [
        Application\ConfigProvider::class,
    ],
    'data/cache/config.php'
);

return $aggregator->getMergedConfig();
