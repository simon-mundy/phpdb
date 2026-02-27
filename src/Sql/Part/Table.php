<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

/**
 * Unified container for FROM, Columns, and Joins.
 *
 * Owns the wiring between these three parts — the prepare() method sets the
 * fromTablePrefix and joinSpecs on Columns before rendering, encapsulating
 * logic that previously lived in Select::buildSqlString().
 */
class Table
{
    private From $from;
    private Columns $columns;
    private ?Joins $joins = null;

    /** Backward-compat model exposed via getRawState / __get */
    public ?Join $joinModel = null;

    public function __construct()
    {
        $this->from    = new From();
        $this->columns = new Columns();
    }

    // --- FROM management ---

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
        return !$this->from->isEmpty();
    }

    public function resetFrom(): static
    {
        $this->from->set(null);
        return $this;
    }

    // --- Column management ---

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

    // --- Join management ---

    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = Select::SQL_STAR,
        string $type = Join::JOIN_INNER,
    ): static {
        ($this->joins ??= new Joins())->join($name, $on, $columns, $type);
        return $this;
    }

    public function hasJoins(): bool
    {
        return $this->joins !== null && !$this->joins->isEmpty();
    }

    public function resetJoins(): static
    {
        $this->joins = null;
        return $this;
    }

    // --- Prepare (wire parts before render) ---

    public function prepare(SqlProcessor $processor): void
    {
        if ($this->columns->getPrefixColumnsWithTable() && !$this->from->isEmpty()) {
            $this->columns->setFromTablePrefix($this->from->getQuotedPrefix($processor));
        } else {
            $this->columns->setFromTablePrefix('');
        }
        $this->columns->setJoinSpecs($this->joins !== null ? $this->joins->getSpecs() : []);
    }

    // --- PartInterface accessors ---

    public function from(): From
    {
        return $this->from;
    }

    public function columns(): Columns
    {
        return $this->columns;
    }

    public function joins(): ?Joins
    {
        return $this->joins;
    }

    // --- Clone ---

    public function __clone()
    {
        $this->from    = clone $this->from;
        $this->columns = clone $this->columns;
        if ($this->joins !== null) {
            $this->joins = clone $this->joins;
        }
    }
}
