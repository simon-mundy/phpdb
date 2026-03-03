<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

final class LessThanOrEqualTo extends Operator
{
    public function __construct(
        string|ArgumentInterface|ExpressionInterface|SqlInterface $left,
        bool|string|int|float|ArgumentInterface|ExpressionInterface|SqlInterface $right,
    ) {
        $this->operator = self::OPERATOR_LESS_THAN_OR_EQUAL_TO;
        $this->setLeft($left);
        $this->setRight($right);
    }
}
