<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

class From extends AbstractPart
{
    private ?TableIdentifier $ref = null;

    private string|array|TableIdentifier|Select|ExpressionInterface|null $raw = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $table = $this->renderTable($renderer);
        return $table !== null ? 'FROM ' . $table : null;
    }

    public function renderTable(AbstractSqlRenderer $renderer): ?string
    {
        return $this->ref !== null ? $renderer->resolveTable($this->ref, withAlias: true) : null;
    }

    public function isEmpty(): bool
    {
        return $this->ref === null;
    }

    public function set(string|array|TableIdentifier|Select|ExpressionInterface|null $table): static
    {
        if ($table === null) {
            $this->ref = null;
            $this->raw = null;
        } elseif ($table instanceof TableIdentifier) {
            $this->ref = $table;
            $this->raw = $table;
        } else {
            $this->ref = new TableIdentifier($table);
            $this->raw = $table;
        }
        return $this;
    }

    public function get(): string|array|TableIdentifier|Select|ExpressionInterface|null
    {
        return $this->raw;
    }

    public function getQuotedPrefix(AbstractSqlRenderer $renderer): string
    {
        if ($this->ref === null) {
            return '';
        }

        return $renderer->tablePrefix($this->ref);
    }
}
