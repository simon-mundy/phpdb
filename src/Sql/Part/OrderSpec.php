<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;

use function is_string;
use function strcasecmp;
use function trim;

final readonly class OrderSpec
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    public ArgumentInterface|ExpressionInterface $column;
    public string $direction;

    public function __construct(
        ArgumentInterface|ExpressionInterface|string $column,
        string $direction = self::ORDER_ASCENDING,
    ) {
        $this->column    = is_string($column) ? new Identifier($column) : $column;
        $this->direction = strcasecmp(trim($direction), self::ORDER_DESCENDING) === 0
            ? self::ORDER_DESCENDING
            : self::ORDER_ASCENDING;
    }
}
