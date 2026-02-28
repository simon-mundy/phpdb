<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function count;
use function current;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function key;

class Columns extends AbstractPart
{
    /** @var ColumnRef[] */
    private array $columnRefs            = [];
    private array $rawColumns            = [Select::SQL_STAR];
    private bool $prefixColumnsWithTable = true;
    private string $fromTablePrefix      = '';
    /** @var JoinSpec[] */
    private array $joinSpecs = [];

    public function __construct()
    {
        $this->normalizeColumns();
    }

    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
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
                $columnSql = $fromPrefix . $platform->quoteIdentifier($column);
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
            foreach ($this->joinSpecs as $spec) {
                if ($spec->columnRefs === []) {
                    continue;
                }
                $joinPrefix = $processor->getQuotedPrefix($spec->table);
                foreach ($spec->columnRefs as $ref) {
                    if ($ref->isStar) {
                        $fragments[] = $joinPrefix . '*';
                        continue;
                    }

                    $column = $ref->column;

                    if (is_string($column)) {
                        $columnSql = $joinPrefix . $platform->quoteIdentifier($column);
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

    public function setFromTablePrefix(string $prefix): static
    {
        $this->fromTablePrefix = $prefix;
        return $this;
    }

    public function addJoinSpec(JoinSpec $spec): static
    {
        $this->joinSpecs[] = $spec;
        return $this;
    }

    /** @param JoinSpec[] $specs */
    public function setJoinSpecs(array $specs): static
    {
        $this->joinSpecs = $specs;
        return $this;
    }

    private function normalizeColumns(): void
    {
        $this->columnRefs = [];
        foreach ($this->rawColumns as $key => $column) {
            $this->columnRefs[] = new ColumnRef($key, $column);
        }
    }
}
