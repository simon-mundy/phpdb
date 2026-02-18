<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;
use ValueError;

use function count;
use function current;
use function implode;
use function is_array;
use function is_numeric;
use function key;

/**
 * Holds column definitions and renders the column list for SELECT statements.
 * Includes both main table columns and join columns.
 *
 * Columns are normalized to ColumnRef[] at set time. At render time,
 * a flat render list of [prefix, ColumnRef] pairs is built from the main columns
 * and any join columns, then rendered in a single loop with ArgumentType enum matching.
 */
class Columns extends AbstractPart
{
    /** @var ColumnRef[] Normalized column references */
    private array $columnRefs = [];

    /** @var array Raw columns for getRawState() backward compatibility */
    private array $rawColumns = [Select::SQL_STAR];

    private bool $prefixColumnsWithTable = true;

    /**
     * Set during preparePartsForBuild -- the resolved table prefix (e.g. "table".)
     */
    private string $fromTablePrefix = '';

    /** @var JoinSpec[] Join specs for column resolution */
    private array $joinSpecs = [];

    public function __construct()
    {
        // Default: normalize the initial [SQL_STAR] column
        $this->normalizeColumns();
    }

    public function toSql(SqlProcessor $processor): ?string
    {
        $columnFragments = [];
        $exprCounter     = 1;
        $fromPrefix      = $this->fromTablePrefix;

        foreach ($this->columnRefs as $ref) {
            $this->renderColumnRef($ref, $fromPrefix, $processor, $columnFragments, $exprCounter);
        }

        if ($this->joinSpecs !== []) {
            $separator = $processor->identifierSeparator;
            foreach ($this->joinSpecs as $spec) {
                if ($spec->columnRefs === []) {
                    continue;
                }
                $joinPrefix = $processor->resolveTable($spec->alias ?? $spec->table) . $separator;
                foreach ($spec->columnRefs as $ref) {
                    $this->renderColumnRef($ref, $joinPrefix, $processor, $columnFragments, $exprCounter);
                }
            }
        }

        return implode(', ', $columnFragments);
    }

    private function renderColumnRef(
        ColumnRef $ref,
        string $prefix,
        SqlProcessor $processor,
        array &$fragments,
        int &$exprCounter,
    ): void {
        if ($ref->isStar) {
            $fragments[] = $prefix . '*';
            return;
        }

        $columnSql = match ($ref->arg->getType()) {
            ArgumentType::Identifier => $prefix
                . $processor->platform->quoteIdentifierInFragment($ref->arg->getValue()),
            ArgumentType::Select => $processor->renderExpression(
                $ref->arg->getValue(),
                $ref->alias ?? 'column',
            ),
            ArgumentType::Literal => $ref->arg->getValue(),
            default => throw new ValueError('Unexpected ArgumentType: ' . $ref->arg->getType()->name),
        };

        if ($ref->alias !== null) {
            $fragments[] = $columnSql . ' AS ' . $processor->platform->quoteIdentifier($ref->alias);
        } elseif ($ref->containsAlias) {
            $fragments[] = $columnSql;
        } else {
            $fragments[] = $columnSql . ' AS Expression' . $exprCounter++;
        }
    }

    public function isEmpty(): bool
    {
        return $this->rawColumns === [];
    }

    public function set(array $columns): static
    {
        $this->rawColumns = $columns;
        $this->normalizeColumns();
        return $this;
    }

    public function add(array|ExpressionInterface|string $column, ?string $alias = null): static
    {
        if (is_array($column)) {
            $key    = key($column);
            $alias  = ! is_numeric($key) ? $key : null;
            $column = current($column);
        }

        if ($alias !== null) {
            $this->rawColumns[$alias] = $column;
            $this->columnRefs[]       = new ColumnRef($alias, $column);
        } else {
            $this->rawColumns[] = $column;
            $this->columnRefs[] = new ColumnRef(count($this->columnRefs), $column);
        }
        return $this;
    }

    /**
     * Reconstruct the original format for getRawState() compatibility.
     */
    public function get(): array
    {
        return $this->rawColumns;
    }

    public function setPrefixColumnsWithTable(bool $prefix): static
    {
        $this->prefixColumnsWithTable = $prefix;
        return $this;
    }

    public function getPrefixColumnsWithTable(): bool
    {
        return $this->prefixColumnsWithTable;
    }

    /**
     * Set the table prefix for column resolution (e.g. "table".)
     */
    public function setFromTablePrefix(string $prefix): static
    {
        $this->fromTablePrefix = $prefix;
        return $this;
    }

    /**
     * Set join specs for column resolution during rendering.
     *
     * @param JoinSpec[] $specs
     */
    public function setJoinSpecs(array $specs): static
    {
        $this->joinSpecs = $specs;
        return $this;
    }

    /**
     * Normalize the raw columns array into ColumnRef[].
     */
    private function normalizeColumns(): void
    {
        $this->columnRefs = [];
        foreach ($this->rawColumns as $key => $column) {
            $this->columnRefs[] = new ColumnRef($key, $column);
        }
    }
}
