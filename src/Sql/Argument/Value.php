<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

final readonly class Value implements ArgumentInterface
{
    /**
     * @param null|string|int|float|bool $value Scalar value, or null
     */
    public function __construct(
        public null|string|int|float|bool $value
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Value;
    }

    public function getValue(): null|string|int|float|bool
    {
        return $this->value;
    }
}
