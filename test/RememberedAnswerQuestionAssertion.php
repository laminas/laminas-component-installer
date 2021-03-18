<?php

declare(strict_types=1);

namespace LaminasTest\ComponentInstaller;

use function assert;
use function sprintf;
use function strpos;

/**
 * @psalm-immutable
 */
final class RememberedAnswerQuestionAssertion extends AbstractQuestionAssertion
{
    private const REMEMBER_QUESTION = 'Remember this option for other packages of the same type';

    /** @var bool */
    public $remember;

    /**
     * @psalm-param non-empty-string $expectedQuestion
     * @psalm-param scalar           $expectedAnswer
     */
    private function __construct(string $expectedQuestion, $expectedAnswer, bool $remember)
    {
        parent::__construct($expectedQuestion, $expectedAnswer);
        $this->remember = $remember;
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
        $question = sprintf('Please select which config file you wish to inject \'%s\' into', $component);
        assert($question !== '');
        return self::create(
            $question,
            $chosen,
            $remember
        );
    }

    /**
     * @return callable(mixed):bool
     */
    public function rememberAnswerAssertion(): callable
    {
        return static function (string $question): bool {
            return strpos($question, self::REMEMBER_QUESTION) !== false;
        };
    }
}
