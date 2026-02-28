<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

/**
 * Holds and renders a table reference with optional alias.
 * Used by Select (FROM), Insert (INTO), Update (UPDATE), Delete (DELETE FROM).
 * Normalizes input to TableRef at set time — alias extraction happens once, not at render time.
 */
class From extends AbstractPart
{
    private ?TableRef $ref = null;

    private ?string $resolvedTable = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->ref === null) {
            return null;
        }

        $resolved = $this->resolvedTable ?? $processor->resolveTable($this->ref->table);

        if ($this->ref->alias !== null) {
            $quotedAlias = $processor->platform->quoteIdentifier($this->ref->alias);
            $resolved    = $processor->renderTable($resolved, $quotedAlias);
        }

        return $resolved;
    }

    public function isEmpty(): bool
    {
        return $this->ref === null;
    }

    public function set(string|array|TableIdentifier|Select|null $table): static
    {
        if ($table === null) {
            $this->ref = null;
        } else {
            $this->ref = new TableRef($table);
        }
        $this->resolvedTable = null;
        return $this;
    }

    /**
     * Reconstruct the original format for getRawState() compatibility.
     */
    public function get(): string|array|TableIdentifier|Select|null
    {
        if ($this->ref === null) {
            return null;
        }

        if ($this->ref->alias !== null) {
            return [$this->ref->alias => $this->ref->table];
        }

        return $this->ref->table;
    }

    /**
     * Get the quoted table prefix for column prefixing (e.g. "table".)
     * Returns the alias if one is set, otherwise the table name.
     */
    public function getQuotedPrefix(SqlProcessor $processor): string
    {
        if ($this->ref === null) {
            return '';
        }

        if ($this->ref->alias !== null) {
            return $processor->platform->quoteIdentifier($this->ref->alias)
                . $processor->identifierSeparator;
        }

        $this->resolvedTable = $processor->resolveTable($this->ref->table);
        if ($this->resolvedTable) {
            return $this->resolvedTable . $processor->identifierSeparator;
        }

        return '';
    }
}
