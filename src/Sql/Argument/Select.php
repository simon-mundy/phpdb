<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

final readonly class Select implements ArgumentInterface
{
    public function __construct(
        public ExpressionInterface|SqlInterface $select
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Select;
    }

    public function getValue(): ExpressionInterface|SqlInterface
    {
        return $this->select;
    }
}
