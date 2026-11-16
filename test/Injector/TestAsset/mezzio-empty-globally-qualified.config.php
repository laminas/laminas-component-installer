<?php

$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([
], 'data/cache/config.php');

return $aggregator->getMergedConfig();
