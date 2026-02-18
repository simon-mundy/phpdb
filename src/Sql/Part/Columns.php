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

    public function toSql(SqlProcessor $processor): ?string
    {
        $columnFragments = [];
        $exprCounter     = 1;
        $fromPrefix      = $this->fromTablePrefix;

        foreach ($this->columnRefs as $ref) {
            $this->renderColumnRef($ref, $fromPrefix, $processor, $columnFragments, $exprCounter);
        }

        if ($this->joinColumnGroups !== []) {
            $separator = $processor->platform->getIdentifierSeparator();
            foreach ($this->joinColumnGroups as $group) {
                $joinPrefix = $group['prefix'] . $separator;
                foreach ($group['columnRefs'] as $ref) {
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
            ArgumentType::Identifier => $prefix . $processor->platform->quoteIdentifierInFragment($ref->arg->getValue()),
            ArgumentType::Select     => $processor->processExpression($ref->arg->getValue(), $ref->alias ?? 'column'),
            ArgumentType::Literal    => $ref->arg->getValue(),
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
     * Set pre-normalized join column groups.
     * Each entry has 'prefix' (resolved table name) and 'columnRefs' (ColumnRef[]).
     *
     * @param array<int, array{prefix: string, columnRefs: ColumnRef[]}> $groups
     */
    public function setJoinColumnGroups(array $groups): void
    {
        $this->joinColumnGroups = $groups;
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
