<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller;

use Laminas\ComponentInstaller\Injector\InjectorInterface;

/**
 * @internal
 */
final class ConfigOption
{
    /**
     * @param non-empty-string $promptText
     */
    public function __construct(private readonly string $promptText, private readonly InjectorInterface $injector)
    {
    }

    /**
     * @return non-empty-string
     */
    public function getPromptText(): string
    {
        return $this->promptText;
    }

    public function getInjector(): InjectorInterface
    {
        return $this->injector;
    }
}
