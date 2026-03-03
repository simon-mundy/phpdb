<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\TableIdentifier;

final readonly class JoinSpec
{
    public bool $isExpressionOn;

    public function __construct(
        public TableIdentifier $table,
        public PredicateInterface|string $on,
        public string $type,
    ) {
        $this->isExpressionOn = $on instanceof ExpressionInterface;
    }
}
