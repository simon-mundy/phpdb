<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

final readonly class Literal implements ArgumentInterface
{
    public function __construct(
        private string $literal
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

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        return $this->literal;
    }
}
