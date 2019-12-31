<?php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator(array(
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
