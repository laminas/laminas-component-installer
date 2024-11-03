<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use function sprintf;
use function str_contains;

/**
 * @psalm-immutable
 */
final class RememberedAnswerQuestionAssertion extends AbstractQuestionAssertion
{
    private const REMEMBER_QUESTION = 'Remember this option for other packages of the same type';

    /**
     * @psalm-param non-empty-string $expectedQuestion
     * @psalm-param scalar           $expectedAnswer
     */
    private function __construct(string $expectedQuestion, $expectedAnswer, public bool $remember)
    {
        parent::__construct($expectedQuestion, $expectedAnswer);
    }

    /**
     * @psalm-param non-empty-string $question
     * @psalm-param scalar           $answer
     */
    public static function create(string $question, $answer, bool $remember): self
    {
        return new self($question, $answer, $remember);
    }

    public static function inject(string $component, int $chosen, bool $remember): self
    {
        return self::create(
            sprintf('Please select which config file you wish to inject \'%s\' into', $component),
            $chosen,
            $remember
        );
    }

    /**
     * @return callable(mixed):bool
     */
    public function rememberAnswerAssertion(): callable
    {
        return static fn(string $question): bool => str_contains($question, self::REMEMBER_QUESTION);
    }
}
