<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ComponentInstaller;

class ConfigOption
{
    /**
     * @var Injector\InjectorInterface
     */
    private $injector;

    /**
     * @var string
     */
    private $promptText;

    /**
     * @param string $promptText
     * @param Injector\InjectorInterface $injector
     */
    public function __construct($promptText, Injector\InjectorInterface $injector)
    {
        $this->promptText = $promptText;
        $this->injector = $injector;
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
