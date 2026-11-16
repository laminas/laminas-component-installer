<?php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    \Foo\Bar::class,
    Application\ConfigProvider::class,
], 'data/cache/config.php');

return $aggregator->getMergedConfig();
