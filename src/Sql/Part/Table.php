<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

/**
 * Holds and renders a table reference with optional alias.
 * Used by Select (FROM), Insert (INTO), Update (UPDATE), Delete (DELETE FROM).
 */
class Table extends AbstractPart
{
    private string|array|TableIdentifier|Select|null $table = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->table === null) {
            return null;
        }

        $alias = null;
        $table = $this->table;

        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
        }

        $resolved = $processor->resolveTable($table);

        if ($alias) {
            $quotedAlias = $processor->platform->quoteIdentifier($alias);
            $resolved    = $processor->renderTable($resolved, $quotedAlias);
        }

        return $resolved;
    }

    public function isEmpty(): bool
    {
        return $this->table === null;
    }

    public function set(string|array|TableIdentifier|Select|null $table): void
    {
        $this->table = $table;
    }

    public function get(): string|array|TableIdentifier|Select|null
    {
        return $this->table;
    }

    /**
     * Get the quoted table prefix for column prefixing (e.g. "table".)
     * Returns the alias if one is set, otherwise the table name.
     */
    public function getQuotedPrefix(SqlPartProcessor $processor): string
    {
        if ($this->table === null) {
            return '';
        }

        $table = $this->table;
        $alias = null;

        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
        }

        if ($alias) {
            return $processor->platform->quoteIdentifier($alias)
                . $processor->platform->getIdentifierSeparator();
        }

        $resolved = $processor->resolveTable($table);
        if ($resolved) {
            return $resolved . $processor->platform->getIdentifierSeparator();
        }

        return '';
    }
}
