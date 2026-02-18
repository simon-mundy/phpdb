<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;

use function strcasecmp;
use function trim;

/**
 * Normalized order-by item with column (as ArgumentInterface) and direction.
 * Parsing of "column DESC" strings and direction normalization happens at construction time.
 */
final readonly class OrderSpec
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    public ArgumentInterface $column;
    public string $direction;

    public function __construct(
        ExpressionInterface|string $column,
        string $direction = self::ORDER_ASCENDING,
    ) {
        if ($column instanceof ExpressionInterface) {
            $this->column = new SelectArgument($column);
        } else {
            $this->column = new Identifier($column);
        }

        $this->direction = strcasecmp(trim($direction), self::ORDER_DESCENDING) === 0
            ? self::ORDER_DESCENDING
            : self::ORDER_ASCENDING;
    }
}
