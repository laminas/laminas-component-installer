<?php

$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator(array(
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
