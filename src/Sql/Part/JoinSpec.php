<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

final readonly class JoinSpec
{
    public bool $isExpressionOn;

    public function __construct(
        public TableIdentifier|Select|ExpressionInterface $table,
        public ?string $alias,
        public PredicateInterface|string $on,
        public string $type,
    ) {
        $this->isExpressionOn = $on instanceof ExpressionInterface;
    }
}
