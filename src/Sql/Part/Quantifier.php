<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;

use function is_string;

class Quantifier extends AbstractPart
{
    private ExpressionInterface|string|null $quantifier = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->quantifier === null) {
            return null;
        }

        if (is_string($this->quantifier)) {
            return $this->quantifier;
        }

        return $processor->renderExpression($this->quantifier, 'quantifier');
    }

    public function isEmpty(): bool
    {
        return $this->quantifier === null;
    }

    public function set(string|ExpressionInterface|null $quantifier): static
    {
        $this->quantifier = $quantifier;
        return $this;
    }

    public function get(): string|ExpressionInterface|null
    {
        return $this->quantifier;
    }
}
