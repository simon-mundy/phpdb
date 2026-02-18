<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

/**
 * Composes Quantifier + Columns + Table into the full SELECT ... FROM ... clause.
 * Eliminates the multi-template branching of the old processSelect().
 */
class SelectClause extends AbstractPart
{
    public function __construct(
        private Quantifier $quantifier,
        private Columns $columns,
        private Table $table,
    ) {
    }

    public function toSql(SqlPartProcessor $processor): string
    {
        $parts = ['SELECT'];

        $quantifierSql = $this->quantifier->toSql($processor);
        if ($quantifierSql !== null) {
            $parts[] = $quantifierSql;
        }

        $columnsSql = $this->columns->toSql($processor);
        if ($columnsSql !== null) {
            $parts[] = $columnsSql;
        }

        $tableSql = $this->table->toSql($processor);
        if ($tableSql !== null) {
            $parts[] = 'FROM';
            $parts[] = $tableSql;
        }

        return implode(' ', $parts);
    }

    public function isEmpty(): bool
    {
        return false; // SELECT always renders
    }
}
