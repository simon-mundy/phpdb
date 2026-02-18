<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function get_debug_type;
use function implode;
use function is_string;
use function sprintf;

/**
 * Wraps a Join model and renders all JOIN clauses.
 * Used by Select and Update.
 *
 * JoinSpecs are normalized at join() time. The rendering loop uses typed access
 * on JoinSpec — no instanceof/is_array cascades or per-render allocations.
 */
class Joins extends AbstractPart
{
    public ?Join $model = null;

    /** @var JoinSpec[] Normalized join specifications, built at join() time */
    private array $specs = [];

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->specs === []) {
            return null;
        }

        $joinSqlParts = [];

        foreach ($this->specs as $j => $spec) {
            $joinName  = $this->resolveJoinTable($spec, $processor);
            $quotedAlias = $spec->alias !== null
                ? $processor->platform->quoteIdentifier($spec->alias)
                : null;

            $renderedTable = $processor->renderTable($joinName, $quotedAlias);

            if ($spec->isExpressionOn) {
                $onClause = $processor->processExpression(
                    $spec->on,
                    'join' . ($j + 1) . 'part'
                );
            } else {
                $onClause = $processor->platform->quoteIdentifierInFragment(
                    $spec->on,
                    ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>']
                );
            }

            $joinSqlParts[] = "{$spec->type} JOIN {$renderedTable} ON {$onClause}";
        }

        return implode(' ', $joinSqlParts);
    }

    public function isEmpty(): bool
    {
        return $this->specs === [];
    }

    /** @return JoinSpec[] */
    public function getSpecs(): array
    {
        return $this->specs;
    }

    /**
     * Add a join specification.
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = Select::SQL_STAR,
        string $type = Join::JOIN_INNER
    ): void {
        $this->model ??= new Join();
        $this->model->join($name, $on, $columns, $type);

        // Normalize eagerly — the last join added is the one we just created
        $rawJoins = $this->model->getJoins();
        $this->specs[] = new JoinSpec($rawJoins[count($rawJoins) - 1]);
    }

    public function reset(): void
    {
        $this->model = null;
        $this->specs = [];
    }

    public function __clone()
    {
        if ($this->model !== null) {
            $this->model = clone $this->model;
        }
    }

    /**
     * Resolve the join table name to its SQL string representation.
     * Dispatches on the known typed alternatives stored in JoinSpec.
     */
    private function resolveJoinTable(JoinSpec $spec, SqlPartProcessor $processor): string
    {
        $table = $spec->table;

        if ($table instanceof Expression) {
            return $table->getExpression();
        }

        if ($table instanceof TableIdentifier) {
            $parts = $table->getTableAndSchema();
            return ($parts[1]
                    ? $processor->platform->quoteIdentifier($parts[1])
                        . $processor->platform->getIdentifierSeparator()
                    : '') . $processor->platform->quoteIdentifier($parts[0]);
        }

        if ($table instanceof Select) {
            return '(' . $processor->processSubSelect($table) . ')';
        }

        if (! is_string($table)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Join name expected to be Expression|TableIdentifier|Select|string, "%s" given',
                get_debug_type($table)
            ));
        }

        return $processor->platform->quoteIdentifier($table);
    }
}
