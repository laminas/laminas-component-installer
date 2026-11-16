<?php

$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([
    \Foo\Bar::class,
    \Application\ConfigProvider::class,
], 'data/cache/config.php');

return $aggregator->getMergedConfig();
