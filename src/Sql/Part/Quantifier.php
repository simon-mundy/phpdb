<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Quantifier extends AbstractPart
{
    private Literal|SelectArgument|null $quantifier = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->quantifier === null) {
            return null;
        }

        return $this->quantifier instanceof Literal
            ? $this->quantifier->literal
            : $renderer->render($this->quantifier->getValue(), 'quantifier');
    }

    public function isEmpty(): bool
    {
        return $this->quantifier === null;
    }

    public function set(ExpressionInterface|string $quantifier): static
    {
        $this->quantifier = $quantifier instanceof ExpressionInterface
            ? new SelectArgument($quantifier)
            : new Literal($quantifier);
        return $this;
    }

    public function get(): ExpressionInterface|string|null
    {
        return $this->quantifier?->getValue();
    }
}
