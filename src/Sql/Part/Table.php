<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function array_shift;
use function count;
use function current;
use function is_array;
use function is_string;
use function key;
use function sprintf;

class Table
{
    private From $from;
    private Columns $columns;
    private ?Join $join = null;

    public function __construct()
    {
        $this->from    = new From();
        $this->columns = new Columns($this->from);
    }

    public function setFrom(string|array|TableIdentifier|Select|null $table): static
    {
        $this->from->set($table);
        return $this;
    }

    public function getFrom(): TableIdentifier|Select|ExpressionInterface|null
    {
        return $this->from->get();
    }

    public function hasFrom(): bool
    {
        return ! $this->from->isEmpty();
    }

    public function resetFrom(): static
    {
        $this->from->set(null);
        return $this;
    }

    public function setColumns(array $columns): static
    {
        $this->columns->set($columns);
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns->get();
    }

    public function setPrefixColumnsWithTable(bool $prefix): static
    {
        $this->columns->setPrefixColumnsWithTable($prefix);
        return $this;
    }

    public function getPrefixColumnsWithTable(): bool
    {
        return $this->columns->getPrefixColumnsWithTable();
    }

    public function resetColumns(): static
    {
        $this->columns->set([]);
        return $this;
    }

    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = Select::SQL_STAR,
        string $type = Join::JOIN_INNER,
    ): static {
        $spec = self::createJoinSpec($name, $on, $columns, $type);
        ($this->join ??= new Join())->add($spec);

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($columns !== []) {
            $refs = [];
            foreach ($columns as $key => $column) {
                $refs[] = new ColumnRef($key, $column, $spec->table, $spec->alias);
            }
            $this->columns->addJoinRefs($refs);
        }

        return $this;
    }

    public static function createJoinSpec(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|int|string $columns = [Select::SQL_STAR],
        string $type = Join::JOIN_INNER,
    ): JoinSpec {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            throw new InvalidArgumentException(
                sprintf("join() expects '%s' as a single element associative array", array_shift($name))
            );
        }

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $alias = null;
        if (is_array($name)) {
            $alias = key($name);
            $name  = current($name);
        }

        $table = $name instanceof TableIdentifier ? $name : (is_string($name) ? new TableIdentifier($name) : $name);

        return new JoinSpec($table, $alias, $on, $type);
    }

    public function hasJoins(): bool
    {
        return $this->join !== null && ! $this->join->isEmpty();
    }

    public function resetJoins(): static
    {
        $this->join = null;
        $this->columns->setJoinRefs([]);
        return $this;
    }

    public function from(): From
    {
        return $this->from;
    }

    public function columns(): Columns
    {
        return $this->columns;
    }

    public function joins(): ?Join
    {
        return $this->join;
    }

    public function __clone()
    {
        $this->from    = clone $this->from;
        $this->columns = clone $this->columns;
        $this->columns->setFrom($this->from);
        if ($this->join !== null) {
            $this->join = clone $this->join;
        }
    }
}
