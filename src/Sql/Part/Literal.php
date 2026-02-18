<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

/**
 * A Part that renders a fixed SQL string, or null if empty.
 * Used for statement keywords, parentheses, and other fixed fragments.
 */
class Literal extends AbstractPart
{
    public function __construct(private ?string $literal = null)
    {
    }

    public function toSql(SqlPartProcessor $processor): ?string
    {
        return $this->literal;
    }

    public function isEmpty(): bool
    {
        return $this->literal === null;
    }

    public function setLiteral(?string $literal): void
    {
        $this->literal = $literal;
    }
}
