<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Join;
use PhpDb\Sql\Select;

use function implode;
use function is_scalar;
use function is_string;
use function key;
use function stripos;

/**
 * Holds column definitions and renders the column list for SELECT statements.
 * Includes both main table columns and join columns.
 */
class Columns extends AbstractPart
{
    private array $columns = [Select::SQL_STAR];
    private bool $prefixColumnsWithTable = true;

    /**
     * Set during preparePartsForBuild -- the resolved table prefix (e.g. "table".)
     */
    private string $fromTablePrefix = '';

    /**
     * Set during preparePartsForBuild -- join column info
     * @var array<int, array{name: string, columns: array}>
     */
    private array $joinColumnInfo = [];

    public function toSql(SqlPartProcessor $processor): ?string
    {
        $expr           = 1;
        $fromTable      = $this->fromTablePrefix;
        $columnFragments = [];

        foreach ($this->columns as $columnIndexOrAs => $column) {
            if ($column === Select::SQL_STAR) {
                $columnFragments[] = "{$fromTable}*";
                continue;
            }

            $columnName = $processor->resolveColumnValue(
                [
                    'column'       => $column,
                    'fromTable'    => $fromTable,
                    'isIdentifier' => true,
                ],
                is_string($columnIndexOrAs) ? $columnIndexOrAs : 'column'
            );

            $columnAs = null;
            if (is_string($columnIndexOrAs)) {
                $columnAs = $processor->platform->quoteIdentifier($columnIndexOrAs);
            } elseif (stripos($columnName, ' as ') === false) {
                $columnAs = is_string($column) ? $processor->platform->quoteIdentifier($column) : 'Expression' . $expr++;
            }

            if ($columnAs !== null) {
                $columnFragments[] = $columnName . ' AS ' . $columnAs;
            } else {
                $columnFragments[] = $columnName;
            }
        }

        // Add join columns
        foreach ($this->joinColumnInfo as $joinInfo) {
            $joinTableName = $joinInfo['name'];

            foreach ($joinInfo['columns'] as $jKey => $jColumn) {
                $jFromTable = is_scalar($jColumn)
                    ? $joinTableName . $processor->platform->getIdentifierSeparator()
                    : '';

                $jColumnName = $processor->resolveColumnValue(
                    [
                        'column'       => $jColumn,
                        'fromTable'    => $jFromTable,
                        'isIdentifier' => true,
                    ],
                    is_string($jKey) ? $jKey : 'column'
                );

                if (is_string($jKey)) {
                    $jAlias = $processor->platform->quoteIdentifier($jKey);
                    $columnFragments[] = $jColumnName . ' AS ' . $jAlias;
                } elseif ($jColumn !== Select::SQL_STAR) {
                    $jAlias = $processor->platform->quoteIdentifier($jColumn);
                    $columnFragments[] = $jColumnName . ' AS ' . $jAlias;
                } else {
                    $columnFragments[] = $jColumnName;
                }
            }
        }

        return implode(', ', $columnFragments);
    }

    public function isEmpty(): bool
    {
        return $this->columns === [];
    }

    public function set(array $columns): void
    {
        $this->columns = $columns;
    }

    public function get(): array
    {
        return $this->columns;
    }

    public function setPrefixColumnsWithTable(bool $prefix): void
    {
        $this->prefixColumnsWithTable = $prefix;
    }

    public function getPrefixColumnsWithTable(): bool
    {
        return $this->prefixColumnsWithTable;
    }

    /**
     * Set the table prefix for column resolution (e.g. "table".)
     */
    public function setFromTablePrefix(string $prefix): void
    {
        $this->fromTablePrefix = $prefix;
    }

    /**
     * Set join column info for resolving join columns.
     * @param array<int, array{name: string, columns: array}> $joinColumnInfo
     */
    public function setJoinColumnInfo(array $joinColumnInfo): void
    {
        $this->joinColumnInfo = $joinColumnInfo;
    }
}
