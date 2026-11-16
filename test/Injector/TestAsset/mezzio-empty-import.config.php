<?php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
], 'data/cache/config.php');

return $aggregator->getMergedConfig();
