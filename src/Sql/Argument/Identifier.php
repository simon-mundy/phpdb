<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

final readonly class Identifier implements ArgumentInterface
{
    public function __construct(
        private string $identifier
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

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        return $processor->platform->quoteIdentifier($this->identifier);
    }
}
