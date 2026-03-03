<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;

use function array_merge;
use function count;
use function current;
use function implode;
use function is_array;
use function is_numeric;
use function key;

class Columns extends AbstractPart
{
    /** @var ColumnRef[] */
    private array $columnRefs            = [];
    private array $rawColumns            = [Select::SQL_STAR];
    private bool $prefixColumnsWithTable = true;
    private string $fromTablePrefix      = '';
    /** @var ColumnRef[] */
    private array $joinRefs = [];

    public function __construct()
    {
        $this->normalizeColumns();
    }

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $refs       = $this->columnRefs;
        $fromPrefix = $this->fromTablePrefix;

        if (isset($refs[0]) && ! isset($refs[1]) && $refs[0]->isStar && $this->joinRefs === []) {
            return $fromPrefix . '*';
        }

        $fragments   = [];
        $exprCounter = 1;
        $pi          = 0;
        $platform    = $renderer->platform;
        $allRefs     = $this->joinRefs !== [] ? array_merge($refs, $this->joinRefs) : $refs;

        foreach ($allRefs as $ref) {
            $prefix = $ref->table !== null
                ? $renderer->renderResolvedTable($ref->table, $ref->tableAlias)
                : $fromPrefix;

            if ($ref->isStar) {
                $fragments[] = $prefix . '*';
                continue;
            }

            $column = $ref->column;

            if ($column instanceof Identifier) {
                $columnSql = $prefix
                    . ($renderer->identifier[$column->identifier]
                        ??= $platform->quoteIdentifier($column->identifier));
            } elseif ($column instanceof ArgumentInterface) {
                $columnSql = $prefix . $renderer->renderArgument($column, '', $pi);
            } else {
                $columnSql = $renderer->render($column, $ref->columnAlias ?? 'column');
            }

            if ($ref->columnAlias !== null) {
                $fragments[] = $columnSql . ' AS '
                    . ($renderer->identifier[$ref->columnAlias]
                        ??= $platform->quoteIdentifier($ref->columnAlias));
            } elseif ($ref->containsAlias) {
                $fragments[] = $columnSql;
            } else {
                $fragments[] = $columnSql . ' AS Expression' . $exprCounter++;
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

    public function add(array|ArgumentInterface|ExpressionInterface|string $column, ?string $alias = null): static
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

    /** @param ColumnRef[] $refs */
    public function addJoinRefs(array $refs): static
    {
        foreach ($refs as $ref) {
            $this->joinRefs[] = $ref;
        }
        return $this;
    }

    /** @param ColumnRef[] $refs */
    public function setJoinRefs(array $refs): static
    {
        $this->joinRefs = $refs;
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
