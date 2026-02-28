<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function array_shift;
use function count;
use function is_array;
use function is_string;
use function key;
use function sprintf;

class Table
{
    private From $from;
    private Columns $columns;
    private Join $join;

    public function __construct()
    {
        $this->from    = new From();
        $this->columns = new Columns();
        $this->join    = new Join();
    }

    public function setFrom(string|array|TableIdentifier|Select|null $table): static
    {
        $this->from->set($table);
        return $this;
    }

    public function getFrom(): string|array|TableIdentifier|Select|null
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
        $this->join->add($spec);
        $this->columns->addJoinSpec($spec);
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

        $raw   = ['name' => $name, 'on' => $on, 'columns' => $columns, 'type' => $type];
        $table = $name instanceof TableIdentifier ? $name : new TableIdentifier($name);

        return new JoinSpec($table, $on, $type, $raw, $columns);
    }

    public function hasJoins(): bool
    {
        return $this->join->isEmpty();
    }

    public function resetJoins(): static
    {
        $this->join->reset();
        $this->columns->setJoinSpecs([]);
        return $this;
    }

    public function prepare(SqlProcessor $processor): void
    {
        if ($this->columns->getPrefixColumnsWithTable() && ! $this->from->isEmpty()) {
            $this->columns->setFromTablePrefix($this->from->getQuotedPrefix($processor));
        } else {
            $this->columns->setFromTablePrefix('');
        }
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
        $this->join    = clone $this->join;
    }
}
