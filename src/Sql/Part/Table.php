<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function spl_object_id;

class Table
{
    private From $from;
    private Columns $columns;
    private ?Join $join              = null;
    private ?int $preparedPlatformId = null;

    public function __construct()
    {
        $this->from    = new From();
        $this->columns = new Columns();
    }

    public function setFrom(string|array|TableIdentifier|Select|null $table): static
    {
        $this->from->set($table);
        $this->preparedPlatformId = null;
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
        $this->preparedPlatformId = null;
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
        $this->preparedPlatformId = null;
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
        ($this->join ??= new Join())->join($name, $on, $columns, $type);
        $this->preparedPlatformId = null;
        return $this;
    }

    public function hasJoins(): bool
    {
        return $this->join !== null && ! $this->join->isEmpty();
    }

    public function resetJoins(): static
    {
        $this->join               = null;
        $this->preparedPlatformId = null;
        return $this;
    }

    public function prepare(SqlProcessor $processor): void
    {
        $platformId = spl_object_id($processor->platform);
        if ($this->preparedPlatformId === $platformId) {
            return;
        }

        if ($this->columns->getPrefixColumnsWithTable() && ! $this->from->isEmpty()) {
            $this->columns->setFromTablePrefix($this->from->getQuotedPrefix($processor));
        } else {
            $this->columns->setFromTablePrefix('');
        }
        $this->columns->setJoinSpecs($this->join !== null ? $this->join->getSpecs() : []);
        $this->preparedPlatformId = $platformId;
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
        if ($this->join !== null) {
            $this->join = clone $this->join;
        }
        $this->preparedPlatformId = null;
    }
}
