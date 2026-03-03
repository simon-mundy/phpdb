<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

final readonly class NullValue implements ArgumentInterface
{
    public function getType(): ArgumentType
    {
        return ArgumentType::Null;
    }

    public function getValue(): null
    {
        return null;
    }
}
