<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

final readonly class Literal implements ArgumentInterface
{
    public function __construct(
        public string $literal
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Literal;
    }

    public function getValue(): string
    {
        return $this->literal;
    }
}
