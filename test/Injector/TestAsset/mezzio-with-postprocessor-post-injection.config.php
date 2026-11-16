<?php
use Laminas\ConfigAggregatorParameters\LazyParameterPostProcessor;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    \Foo\Bar::class,
    \Laminas\Filter\ConfigProvider::class,
    Application\ConfigProvider::class,
], null, [
    new LazyParameterPostProcessor(static fn(): array => (new ConfigAggregator([
        new PhpFileProvider(sprintf(
            '%s/parameters{,*.}(,%s,local}.php',
            __DIR__,
            'someenv'
        )),
    ]))->getMergedConfig()),
]);

return $aggregator->getMergedConfig();
