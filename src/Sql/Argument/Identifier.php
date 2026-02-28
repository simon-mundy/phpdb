<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

use function explode;
use function implode;

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

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        if (!isset($this->segments[1])) {
            return $processor->platform->quoteIdentifier($this->segments[0]);
        }

        $parts = [];
        foreach ($this->segments as $s) {
            $parts[] = $processor->platform->quoteIdentifier($s);
        }
        return implode($processor->identifierSeparator, $parts);
    }
}
