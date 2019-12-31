<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

use PHPUnit\Framework\ExpectationFailedException;

if (class_exists('PHPUnit_Framework_ExpectationFailedException')) {
    class_alias('PHPUnit_Framework_ExpectationFailedException', ExpectationFailedException::class);
}
