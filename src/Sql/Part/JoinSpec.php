<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\TableIdentifier;

final readonly class JoinSpec
{
    public TableIdentifier $table;
    public PredicateInterface|string $on;
    public bool $isExpressionOn;
    public string $type;

    /** @var ColumnRef[] */
    public array $columnRefs;

    /** @var array{name: array|string|TableIdentifier, on: PredicateInterface|string, columns: array, type: string} */
    public array $raw;

    /** @param array{name: array|string|TableIdentifier, on: PredicateInterface|string, columns: array, type: string} $join */
    public function __construct(array $join)
    {
        $this->raw = $join;
        $name      = $join['name'];

        if ($name instanceof TableIdentifier) {
            $this->table = $name;
        } else {
            $this->table = new TableIdentifier($name);
        }

        $on                   = $join['on'];
        $this->on             = $on;
        $this->isExpressionOn = $on instanceof ExpressionInterface;
        $this->type           = $join['type'];

        $refs = [];
        foreach ($join['columns'] as $key => $column) {
            $refs[] = new ColumnRef($key, $column);
        }
        $this->columnRefs = $refs;
    }
}
