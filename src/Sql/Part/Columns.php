<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

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
 * Columns are normalized to ColumnRef[] at set time. Rendering is inlined
 * with instanceof fast-paths for Identifier (hot path) and Literal (star).
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
        $this->normalizeColumns();
    }

    public function toSql(SqlProcessor $processor): ?string
    {
        $refs       = $this->columnRefs;
        $fromPrefix = $this->fromTablePrefix;

        if (isset($refs[0]) && ! isset($refs[1]) && $refs[0]->arg instanceof Literal && $this->joinSpecs === []) {
            return $fromPrefix . $refs[0]->arg->getValue();
        }

        $fragments   = [];
        $exprCounter = 1;
        $platform    = $processor->platform;

        foreach ($refs as $ref) {
            $arg = $ref->arg;

            if ($arg instanceof Literal) {
                $fragments[] = $fromPrefix . $arg->getValue();
                continue;
            }

            if ($arg instanceof Identifier) {
                $segments = $arg->segments;
                if (! isset($segments[1])) {
                    $columnSql = $fromPrefix . $platform->quoteIdentifier($segments[0]);
                } else {
                    $parts = [];
                    foreach ($segments as $s) {
                        $parts[] = $platform->quoteIdentifier($s);
                    }
                    $columnSql = $fromPrefix . implode($processor->identifierSeparator, $parts);
                }
            } else {
                $columnSql = $processor->renderExpression(
                    $arg->getValue(),
                    $ref->alias ?? 'column',
                );
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
                    $arg = $ref->arg;

                    if ($arg instanceof Literal) {
                        $fragments[] = $joinPrefix . $arg->getValue();
                        continue;
                    }

                    if ($arg instanceof Identifier) {
                        $segments = $arg->segments;
                        if (! isset($segments[1])) {
                            $columnSql = $joinPrefix . $platform->quoteIdentifier($segments[0]);
                        } else {
                            $parts = [];
                            foreach ($segments as $s) {
                                $parts[] = $platform->quoteIdentifier($s);
                            }
                            $columnSql = $joinPrefix . implode($separator, $parts);
                        }
                    } else {
                        $columnSql = $processor->renderExpression(
                            $arg->getValue(),
                            $ref->alias ?? 'column',
                        );
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
