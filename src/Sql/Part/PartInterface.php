<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use Stringable;

interface PartInterface extends ExpressionInterface, Stringable
{
    public function isEmpty(): bool;
}
