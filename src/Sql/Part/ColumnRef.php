<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_string;
use function stripos;

final readonly class ColumnRef
{
    public ArgumentInterface|ExpressionInterface|string $column;
    public bool $isStar;
    public ?string $columnAlias;
    public bool $containsAlias;
    public TableIdentifier|Select|ExpressionInterface|null $table;
    public ?string $tableAlias;

    public function __construct(
        int|string $key,
        ArgumentInterface|ExpressionInterface|string $column,
        TableIdentifier|Select|ExpressionInterface|null $table = null,
        ?string $tableAlias = null,
        string $star = Select::SQL_STAR,
    ) {
        $this->table      = $table;
        $this->tableAlias = $tableAlias;
        $this->isStar     = is_string($column) && $column === $star;

        if ($this->isStar) {
            $this->column        = '';
            $this->columnAlias   = null;
            $this->containsAlias = false;
            return;
        }

        $this->column = is_string($column) ? new Identifier($column) : $column;

        if (is_string($key)) {
            $this->columnAlias   = $key;
            $this->containsAlias = false;
            return;
        }

        if ($column instanceof ExpressionInterface) {
            $this->containsAlias = $column instanceof Expression
                && stripos($column->getExpression(), ' as ') !== false;
            $this->columnAlias   = null;
            return;
        }

        $this->columnAlias   = $column instanceof ArgumentInterface ? $column->getValue() : $column;
        $this->containsAlias = false;
    }
}
