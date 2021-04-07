<?php

/**
 * @see       https://github.com/laminas/laminas-component-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-component-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-component-installer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use function strpos;

/**
 * @psalm-immutable
 */
abstract class AbstractQuestionAssertion
{
    /**
     * @var string
     * @psalm-var non-empty-string
     */
    public $expectedQuestion;

    /**
     * @var mixed
     * @psalm-var scalar
     */
    public $expectedAnswer;

    /**
     * @psalm-param non-empty-string $expectedQuestion
     * @psalm-param scalar           $expectedAnswer
     */
    protected function __construct(string $expectedQuestion, $expectedAnswer)
    {
        $this->expectedQuestion = $expectedQuestion;
        $this->expectedAnswer   = $expectedAnswer;
    }

    /**
     * @return Closure(string):bool
     */
    final public function assertion(): callable
    {
        return function (string $param): bool {
            return $this->assertQuestionMatchesExpectation($param);
        };
    }

    private function assertQuestionMatchesExpectation(string $argument): bool
    {
        return strpos($argument, $this->expectedQuestion) !== false;
    }
}
