<?php
use Laminas\ConfigAggregatorParameters\LazyParameterPostProcessor;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator(array(
    \Foo\Bar::class,
    \Laminas\Filter\ConfigProvider::class,
    Application\ConfigProvider::class,
), null, [
    new LazyParameterPostProcessor(static function (): array {
        return (new ConfigAggregator([
            new PhpFileProvider(sprintf(
                '%s/parameters{,*.}(,%s,local}.php',
                __DIR__,
                'someenv'
            )),
        ]))->getMergedConfig();
    }),
]);

return $aggregator->getMergedConfig();
