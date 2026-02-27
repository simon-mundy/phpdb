<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function explode;

/**
 * Represents a SQL identifier (table name, column name, alias, etc.).
 * Dot-separated identifiers (e.g. "table.column") are pre-split into segments
 * at construction time so rendering can quote each segment individually
 * without regex.
 */
final readonly class Identifier implements ArgumentInterface
{
    /** @var string[] Pre-split segments (e.g. ['foo','bar'] for 'foo.bar') */
    public array $segments;

    public function __construct(
        private string $identifier
    ) {
        $this->segments = explode('.', $identifier);
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifier;
    }

    public function getValue(): string
    {
        return $this->identifier;
    }

    public function getSpecification(): string
    {
        return '%s';
    }
}
