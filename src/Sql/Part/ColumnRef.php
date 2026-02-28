<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function is_string;
use function stripos;

final readonly class ColumnRef
{
    public ExpressionInterface|string $column;
    public bool $isStar;
    public ?string $alias;
    public bool $containsAlias;

    public function __construct(
        int|string $key,
        ExpressionInterface|string $column,
        string $star = Select::SQL_STAR,
    ) {
        $this->column = $column;
        $this->isStar = ($column === $star);

        if ($this->isStar) {
            $this->alias         = null;
            $this->containsAlias = false;
            return;
        }

        if (is_string($key)) {
            $this->alias         = $key;
            $this->containsAlias = false;
            return;
        }

        if ($column instanceof ExpressionInterface) {
            $this->containsAlias = $column instanceof Expression
                && stripos($column->getExpression(), ' as ') !== false;
            $this->alias         = null;
            return;
        }

        $this->alias         = $column;
        $this->containsAlias = false;
    }
}
