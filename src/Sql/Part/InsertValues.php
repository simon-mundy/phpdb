<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Exception;
use PhpDb\Sql\TableIdentifier;

use function array_keys;
use function array_map;
use function implode;
use function is_scalar;

/**
 * Renders an INSERT INTO table (columns) VALUES (values) statement.
 * Mutually exclusive with InsertSelect -- only one renders.
 */
class InsertValues extends AbstractPart
{
    private string $keyword = 'INSERT INTO';
    private Table $table;

    /** @var array<string, mixed> Column-to-value mapping (keys are column names) */
    private array $columns = [];

    private bool $hasSelect = false;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->hasSelect) {
            return null;
        }

        if ($this->columns === []) {
            throw new Exception\InvalidArgumentException('values or select should be present');
        }

        $columns     = [];
        $values      = [];
        $i           = 0;
        $isPdoDriver = $processor->driver instanceof PdoDriverInterface;

        foreach ($this->columns as $column => $value) {
            $columns[] = $processor->platform->quoteIdentifier($column);
            if (is_scalar($value) && $processor->parameterContainer) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                $paramName = $column;
                if ($isPdoDriver) {
                    $paramName = 'c_' . $i++;
                }

                $values[] = $processor->driver->formatParameterName($paramName);
                $processor->parameterContainer->offsetSet($paramName, $value);
            } else {
                $values[] = $processor->resolveColumnValue($value);
            }
        }

        $tableSql = $processor->resolveTable($this->table->get());

        return $this->keyword . ' ' . $tableSql
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';
    }

    public function isEmpty(): bool
    {
        return $this->hasSelect || $this->columns === [];
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setHasSelect(bool $hasSelect): void
    {
        $this->hasSelect = $hasSelect;
    }
}
