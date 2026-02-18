<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;

/**
 * Holds and renders a SELECT quantifier (DISTINCT, ALL, or expression).
 * Normalizes input to ArgumentInterface at set time.
 */
class Quantifier extends AbstractPart
{
    private ?ArgumentInterface $quantifier = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->quantifier === null) {
            return null;
        }

        return match ($this->quantifier->getType()) {
            ArgumentType::Literal => $this->quantifier->getValue(),
            ArgumentType::Select  => $processor->renderExpression($this->quantifier->getValue(), 'quantifier'),
        };
    }

    public function isEmpty(): bool
    {
        return $this->quantifier === null;
    }

    public function set(string|ExpressionInterface|null $quantifier): void
    {
        if ($quantifier === null) {
            $this->quantifier = null;
        } elseif ($quantifier instanceof ExpressionInterface) {
            $this->quantifier = new SelectArgument($quantifier);
        } else {
            $this->quantifier = new Literal($quantifier);
        }
    }

    /**
     * Reconstruct the original format for getRawState() compatibility.
     */
    public function get(): string|ExpressionInterface|null
    {
        if ($this->quantifier === null) {
            return null;
        }

        return $this->quantifier->getValue();
    }
}
