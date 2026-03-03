<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function current;
use function is_array;
use function is_string;
use function key;

class From extends AbstractPart
{
    private TableIdentifier|Select|ExpressionInterface|null $table = null;

    private ?string $alias = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $table = $this->renderTable($renderer);
        return $table !== null ? 'FROM ' . $table : null;
    }

    public function renderTable(AbstractSqlRenderer $renderer): ?string
    {
        return $this->table !== null
            ? $renderer->renderTableSource($this->table, $this->alias)
            : null;
    }

    public function isEmpty(): bool
    {
        return $this->table === null;
    }

    public function set(string|array|TableIdentifier|Select|ExpressionInterface|null $table): static
    {
        if ($table === null) {
            $this->table = null;
            $this->alias = null;
            return $this;
        }

        $alias = null;
        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
            if ($table instanceof TableIdentifier) {
                $this->table = $table;
                $this->alias = $alias;
                return $this;
            }
        }

        if (is_string($table)) {
            $this->table = new TableIdentifier($table);
        } else {
            $this->table = $table;
        }
        $this->alias = $alias;
        return $this;
    }

    public function get(): TableIdentifier|Select|ExpressionInterface|null
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getQuotedPrefix(AbstractSqlRenderer $renderer): string
    {
        if ($this->table === null) {
            return '';
        }

        return $renderer->renderResolvedTable($this->table, $this->alias);
    }
}
