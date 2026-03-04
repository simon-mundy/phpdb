<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use Stringable;

interface ClauseInterface extends ExpressionInterface, Stringable
{
    public function isEmpty(): bool;
}
