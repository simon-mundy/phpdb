<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\TableIdentifier;

final readonly class JoinSpec
{
    public bool $isExpressionOn;

    /** @var ColumnRef[] */
    public array $columnRefs;

    public function __construct(
        public TableIdentifier $table,
        public PredicateInterface|string $on,
        public string $type,
        public array $raw,
        array $columns,
    ) {
        $this->isExpressionOn = $on instanceof ExpressionInterface;

        $refs = [];
        foreach ($columns as $key => $column) {
            $refs[] = new ColumnRef($key, $column);
        }
        $this->columnRefs = $refs;
    }
}
