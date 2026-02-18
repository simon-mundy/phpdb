<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;

use function array_keys;
use function implode;

/**
 * Renders an INSERT INTO table (columns) SELECT ... statement.
 * Mutually exclusive with InsertValues -- only one renders.
 */
class InsertSelect extends AbstractPart
{
    private string $keyword = 'INSERT INTO';
    private Table $table;
    private ?Select $select = null;

    /** @var array<string, mixed> Column names (keys) from the columns array */
    private array $columns = [];

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->select === null) {
            return null;
        }

        $selectSql = $processor->processSubSelect($this->select);

        $columns = [];
        foreach (array_keys($this->columns) as $name) {
            $columns[] = $processor->platform->quoteIdentifier($name);
        }
        $columnsSql = implode(', ', $columns);

        $tableSql = $processor->resolveTable($this->table->get());

        return $this->keyword . ' ' . $tableSql
            . ' ' . ($columnsSql ? "({$columnsSql})" : '')
            . ' ' . $selectSql;
    }

    public function isEmpty(): bool
    {
        return $this->select === null;
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function setSelect(?Select $select): void
    {
        $this->select = $select;
    }

    public function getSelect(): ?Select
    {
        return $this->select;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }
}
