<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

final readonly class Identifier implements ArgumentInterface
{
    public function __construct(
        public string $identifier
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifier;
    }

    public function getValue(): string
    {
        return $this->identifier;
    }
}
