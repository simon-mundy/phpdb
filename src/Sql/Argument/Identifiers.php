<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function array_fill;
use function count;
use function implode;

/**
 * Represents multiple SQL identifiers (column names for multi-column clauses).
 *
 * Used for multi-column IN predicates like (col1, col2) IN (SELECT ...).
 * Internally stores Identifier[] so each gets pre-split segments and
 * goes through the same renderIdentifierArgument rendering path.
 */
final readonly class Identifiers implements ArgumentInterface
{
    /** @var Identifier[] */
    public array $identifiers;

    /**
     * @param list<string> $identifiers
     */
    public function __construct(array $identifiers)
    {
        $items = [];
        foreach ($identifiers as $id) {
            $items[] = new Identifier($id);
        }
        $this->identifiers = $items;
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifiers;
    }

    /**
     * @return list<string>
     */
    public function getValue(): array
    {
        $result = [];
        foreach ($this->identifiers as $id) {
            $result[] = $id->getValue();
        }
        return $result;
    }

    public function getSpecification(): string
    {
        $count = count($this->identifiers);
        return $count > 0
            ? '(' . implode(', ', array_fill(0, $count, '%s')) . ')'
            : '(NULL)';
    }
}
