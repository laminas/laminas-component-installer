<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use function str_contains;

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
     * @psalm-param non-empty-string $expectedQuestion
     * @psalm-param scalar           $expectedAnswer
     * @param mixed $expectedAnswer
     */
    protected function __construct(string $expectedQuestion, public $expectedAnswer)
    {
        $this->expectedQuestion = $expectedQuestion;
    }

    /**
     * @return Closure(string):bool
     */
    final public function assertion(): callable
    {
        return $this->assertQuestionMatchesExpectation(...);
    }

    private function assertQuestionMatchesExpectation(string $argument): bool
    {
        return str_contains($argument, $this->expectedQuestion);
    }
}
