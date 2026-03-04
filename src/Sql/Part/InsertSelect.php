<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;

use function array_keys;
use function implode;

class InsertSelect extends AbstractPart
{
    private string $keyword = 'INSERT INTO';
    private From $table;
    private ?Select $select = null;

    /** @var array<string, mixed> */
    private array $columns = [];

    public function __construct(From $table)
    {
        $this->table = $table;
    }

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->select === null) {
            return null;
        }

        $selectSql = $renderer->processSubSelect($this->select);

        $columns = [];
        foreach (array_keys($this->columns) as $name) {
            $columns[] = $renderer->qo . $name . $renderer->qc;
        }
        $columnsSql = implode(', ', $columns);

        $tableSql = $this->table->renderTable($renderer);

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
