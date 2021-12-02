<?php

declare(strict_types=1);

namespace Laminas\ComponentInstaller;

class ConfigOption
{
    /** @var Injector\InjectorInterface */
    private $injector;

    /** @var string */
    private $promptText;

    /**
     * @param string $promptText
     */
    public function __construct($promptText, Injector\InjectorInterface $injector)
    {
        $this->promptText = $promptText;
        $this->injector   = $injector;
    }

    /**
     * @return string
     */
    public function getPromptText()
    {
        return $this->promptText;
    }

    /**
     * @return Injector\InjectorInterface
     */
    public function getInjector()
    {
        return $this->injector;
    }
}
