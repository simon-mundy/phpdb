<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function implode;
use function is_numeric;

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

    /** @var array<int, array{prefix: string, columns: ColumnRef[]}> Join column groups */
    private array $joinColumnGroups = [];

    public function __construct()
    {
        // Default: normalize the initial [SQL_STAR] column
        $this->normalizeColumns();
    }

    public function toSql(SqlPartProcessor $processor): ?string
    {
        // Build a flat render list: [prefix, ColumnRef] pairs
        $renderList = [];

        foreach ($this->columnRefs as $ref) {
            $renderList[] = [$this->fromTablePrefix, $ref];
        }

        $separator = $processor->platform->getIdentifierSeparator();
        foreach ($this->joinColumnGroups as $group) {
            $joinPrefix = $group['prefix'] . $separator;
            foreach ($group['columns'] as $ref) {
                $renderList[] = [$joinPrefix, $ref];
            }
        }

        // Render all columns in a single loop
        $columnFragments = [];
        $exprCounter     = 1;

        foreach ($renderList as [$prefix, $ref]) {
            if ($ref->isStar) {
                $columnFragments[] = $prefix . '*';
                continue;
            }

            $columnSql = match ($ref->arg->getType()) {
                ArgumentType::Identifier => $prefix . $processor->platform->quoteIdentifierInFragment($ref->arg->getValue()),
                ArgumentType::Select     => $processor->processExpression($ref->arg->getValue(), $ref->alias ?? 'column'),
                ArgumentType::Literal    => $ref->arg->getValue(),
            };

            if ($ref->alias !== null) {
                $columnFragments[] = $columnSql . ' AS ' . $processor->platform->quoteIdentifier($ref->alias);
            } elseif ($ref->containsAlias) {
                $columnFragments[] = $columnSql;
            } else {
                $columnFragments[] = $columnSql . ' AS Expression' . $exprCounter++;
            }
        }

        return implode(', ', $columnFragments);
    }

    public function isEmpty(): bool
    {
        return $this->rawColumns === [];
    }

    public function set(array $columns): void
    {
        $this->rawColumns = $columns;
        $this->normalizeColumns();
    }

    public function add(array|ExpressionInterface|string $column, ?string $alias = null): void
    {
        if (is_array($column)) {
            $key    = key($column);
            $alias  = ! is_numeric($key) ? $key : null;
            $column = current($column);
        }

        if ($alias !== null) {
            $this->rawColumns[$alias] = $column;
            $this->columnRefs[] = new ColumnRef($alias, $column);
        } else {
            $this->rawColumns[] = $column;
            $this->columnRefs[] = new ColumnRef(count($this->columnRefs), $column);
        }
    }

    /**
     * Reconstruct the original format for getRawState() compatibility.
     */
    public function get(): array
    {
        return $this->rawColumns;
    }

    public function setPrefixColumnsWithTable(bool $prefix): void
    {
        $this->prefixColumnsWithTable = $prefix;
    }

    public function getPrefixColumnsWithTable(): bool
    {
        return $this->prefixColumnsWithTable;
    }

    /**
     * Set the table prefix for column resolution (e.g. "table".)
     */
    public function setFromTablePrefix(string $prefix): void
    {
        $this->fromTablePrefix = $prefix;
    }

    /**
     * Set join column info for resolving join columns.
     * Normalizes raw join column data into ColumnRef[] grouped by table prefix.
     *
     * @param array<int, array{name: string, columns: array}> $joinColumnInfo
     */
    public function setJoinColumnInfo(array $joinColumnInfo): void
    {
        $this->joinColumnGroups = [];
        foreach ($joinColumnInfo as $info) {
            $columnRefs = [];
            foreach ($info['columns'] as $key => $column) {
                $columnRefs[] = new ColumnRef($key, $column);
            }
            $this->joinColumnGroups[] = ['prefix' => $info['name'], 'columns' => $columnRefs];
        }
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
