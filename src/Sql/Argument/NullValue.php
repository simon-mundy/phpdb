<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

/**
 * Represents a SQL NULL value.
 * Renders as the literal string 'NULL' in SQL output.
 * getValue() returns PHP null for raw-state introspection.
 */
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

    public function getSpecification(): string
    {
        return '%s';
    }
}
