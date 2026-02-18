<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;

/**
 * Holds and renders a SELECT quantifier (DISTINCT, ALL, or expression).
 */
class Quantifier extends AbstractPart
{
    private string|ExpressionInterface|null $quantifier = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->quantifier === null) {
            return null;
        }

        if ($this->quantifier instanceof ExpressionInterface) {
            return $processor->processExpression($this->quantifier, 'quantifier');
        }

        return $this->quantifier;
    }

    public function isEmpty(): bool
    {
        return $this->quantifier === null;
    }

    public function set(string|ExpressionInterface|null $quantifier): void
    {
        $this->quantifier = $quantifier;
    }

    public function get(): string|ExpressionInterface|null
    {
        return $this->quantifier;
    }
}
