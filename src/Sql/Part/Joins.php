<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function implode;

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
            $joinName = match ($spec->tableType) {
                JoinTableType::Expression      => $spec->table->getExpression(),
                JoinTableType::TableIdentifier => $processor->resolveTable($spec->table),
                JoinTableType::Select          => '(' . $processor->processSubSelect($spec->table) . ')',
                JoinTableType::Identifier      => $processor->platform->quoteIdentifier($spec->table),
            };
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

}
