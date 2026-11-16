<?php

$aggregator = new Laminas\ConfigAggregator\ConfigAggregator([
    Application\ConfigProvider::class,
], 'data/cache/config.php');

return $aggregator->getMergedConfig();
