<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function count;
use function current;
use function explode;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function key;

/**
 * Holds column definitions and renders the column list for SELECT statements.
 * Includes both main table columns and join columns.
 *
 * Columns are normalized to ColumnRef[] at set time. Rendering is inlined
 * with instanceof fast-paths for Identifier (hot path) and Literal (star).
 */
class Columns extends AbstractPart
{
    /** @var ColumnRef[] Normalized column references */
    private array $columnRefs = [];

    /** @var array Raw columns for getRawState() backward compatibility */
    private array $rawColumns = [Select::SQL_STAR];

    public bool $prefixColumnsWithTable = true;

    /** @var string Resolved table prefix (e.g. "table".) — set by Table::prepare() */
    public string $fromTablePrefix = '';

    /** @var JoinSpec[] Join specs — set by Table::prepare() */
    public array $joinSpecs = [];

    public function __construct()
    {
        $this->normalizeColumns();
    }

    public function toSql(SqlProcessor $processor): ?string
    {
        $refs       = $this->columnRefs;
        $fromPrefix = $this->fromTablePrefix;

        if (isset($refs[0]) && ! isset($refs[1]) && $refs[0]->isStar && $this->joinSpecs === []) {
            return $fromPrefix . '*';
        }

        $fragments   = [];
        $exprCounter = 1;
        $platform    = $processor->platform;

        foreach ($refs as $ref) {
            if ($ref->isStar) {
                $fragments[] = $fromPrefix . '*';
                continue;
            }

            $column = $ref->column;

            if (is_string($column)) {
                $parts     = explode('.', $column, 2);
                $columnSql = $fromPrefix . $platform->quoteIdentifier($parts[0], $parts[1] ?? null);
            } else {
                $columnSql = $processor->renderExpression($column, $ref->alias ?? 'column');
            }

            if ($ref->alias !== null) {
                $fragments[] = $columnSql . ' AS ' . $platform->quoteIdentifier($ref->alias);
            } elseif ($ref->containsAlias) {
                $fragments[] = $columnSql;
            } else {
                $fragments[] = $columnSql . ' AS Expression' . $exprCounter++;
            }
        }

        if ($this->joinSpecs !== []) {
            $separator = $processor->identifierSeparator;
            foreach ($this->joinSpecs as $spec) {
                if ($spec->columnRefs === []) {
                    continue;
                }
                $joinPrefix = $processor->resolveTable($spec->alias ?? $spec->table) . $separator;
                foreach ($spec->columnRefs as $ref) {
                    if ($ref->isStar) {
                        $fragments[] = $joinPrefix . '*';
                        continue;
                    }

                    $column = $ref->column;

                    if (is_string($column)) {
                        $parts     = explode('.', $column, 2);
                        $columnSql = $joinPrefix . $platform->quoteIdentifier($parts[0], $parts[1] ?? null);
                    } else {
                        $columnSql = $processor->renderExpression($column, $ref->alias ?? 'column');
                    }

                    if ($ref->alias !== null) {
                        $fragments[] = $columnSql . ' AS ' . $platform->quoteIdentifier($ref->alias);
                    } elseif ($ref->containsAlias) {
                        $fragments[] = $columnSql;
                    } else {
                        $fragments[] = $columnSql . ' AS Expression' . $exprCounter++;
                    }
                }
            }
        }

        return implode(', ', $fragments);
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
