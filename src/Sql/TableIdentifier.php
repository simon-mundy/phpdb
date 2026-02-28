<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Part\SqlProcessor;

use function current;
use function is_array;
use function is_string;
use function key;

class TableIdentifier
{
    protected string|Select|ExpressionInterface $table;

    protected ?string $schema = null;

    protected ?string $alias = null;

    public function __construct(
        string|array|Select|ExpressionInterface $table,
        ?string $schema = null,
        ?string $alias = null,
    ) {
        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
            if ($table instanceof self) {
                $this->table  = $table->table;
                $this->schema = $table->schema;
                $this->alias  = $alias;
                return;
            }
        }

        if (is_string($table)) {
            if ($table === '') {
                throw new Exception\InvalidArgumentException(
                    '$table must be a valid table name, empty string given'
                );
            }
        } elseif ($schema !== null) {
            throw new Exception\InvalidArgumentException(
                '$schema only applies to string table names'
            );
        }

        if ($schema !== null && $schema === '') {
            throw new Exception\InvalidArgumentException(
                '$schema must be a valid schema name or null, empty string given'
            );
        }

        $this->table  = $table;
        $this->schema = $schema;
        $this->alias  = $alias;
    }

    public function getTable(): string|Select|ExpressionInterface
    {
        return $this->table;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /** @return array{0: string, 1: null|string} */
    public function getTableAndSchema(): array
    {
        if (! is_string($this->table)) {
            throw new Exception\InvalidArgumentException(
                'getTableAndSchema() only applies to string table names'
            );
        }

        return [$this->table, $this->schema];
    }

    public function resolveTable(SqlProcessor $processor): string
    {
        $table = $this->table;

        if (is_string($table)) {
            $resolved = $processor->platform->quoteIdentifier($table);
            if ($this->schema !== null) {
                $resolved = $processor->platform->quoteIdentifier($this->schema)
                    . $processor->identifierSeparator . $resolved;
            }
            return $resolved;
        }

        if ($table instanceof Select) {
            return '(' . $processor->processSubSelect($table) . ')';
        }

        $pi = 0;
        return $table->toSql($processor, '', $pi);
    }

    public function resolveTableWithAlias(SqlProcessor $processor): string
    {
        $resolved = $this->resolveTable($processor);

        if ($this->alias !== null) {
            $resolved .= ' AS ' . $processor->platform->quoteIdentifier($this->alias);
        }

        return $resolved;
    }

    public function getQuotedPrefix(SqlProcessor $processor): string
    {
        if ($this->alias !== null) {
            return $processor->platform->quoteIdentifier($this->alias)
                . $processor->identifierSeparator;
        }

        return $this->resolveTable($processor) . $processor->identifierSeparator;
    }
}
