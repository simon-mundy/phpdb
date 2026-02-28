<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use ValueError;

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
            default => throw new ValueError('Unexpected ArgumentType: ' . $this->quantifier->getType()->name),
        };
    }

    public function isEmpty(): bool
    {
        return $this->quantifier === null;
    }

    public function set(string|ExpressionInterface|null $quantifier): static
    {
        if ($quantifier === null) {
            $this->quantifier = null;
        } elseif ($quantifier instanceof ExpressionInterface) {
            $this->quantifier = new SelectArgument($quantifier);
        } else {
            $this->quantifier = new Literal($quantifier);
        }
        return $this;
    }

    public function get(): string|ExpressionInterface|null
    {
        if ($this->quantifier === null) {
            return null;
        }

        return $this->quantifier->getValue();
    }
}
