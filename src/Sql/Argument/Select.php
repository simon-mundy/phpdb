<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\SqlInterface;

final readonly class Select implements ArgumentInterface
{
    public function __construct(
        private ExpressionInterface|SqlInterface $select
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Select;
    }

    public function getValue(): ExpressionInterface|SqlInterface
    {
        return $this->select;
    }

    public function render(AbstractSqlRenderer $renderer, string $paramPrefix, int &$paramIndex): string
    {
        if ($this->select instanceof \PhpDb\Sql\Select) {
            return '(' . $renderer->processSubSelect($this->select) . ')';
        }

        return $this->select->toSql($renderer, $paramPrefix, $paramIndex);
    }
}
