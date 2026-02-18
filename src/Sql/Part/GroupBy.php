<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentType;
use ValueError;

use function implode;
use function is_array;

/**
 * Holds and renders GROUP BY clause.
 * Normalizes columns to ColumnRef at add time.
 */
class GroupBy extends AbstractPart
{
    /** @var ColumnRef[]|null */
    private ?array $group = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->group === null) {
            return null;
        }

        $groups = [];
        foreach ($this->group as $ref) {
            $groups[] = match ($ref->arg->getType()) {
                ArgumentType::Identifier => $processor->platform->quoteIdentifierInFragment($ref->arg->getValue()),
                ArgumentType::Select     => $processor->renderExpression($ref->arg->getValue()),
                ArgumentType::Literal    => $ref->arg->getValue(),
                default => throw new ValueError('Unexpected ArgumentType: ' . $ref->arg->getType()->name),
            };
        }

        return 'GROUP BY ' . implode(', ', $groups);
    }

    public function isEmpty(): bool
    {
        return $this->group === null;
    }

    public function add(mixed $group): void
    {
        if (is_array($group)) {
            foreach ($group as $g) {
                $this->group[] = new ColumnRef(0, $g);
            }
        } else {
            $this->group[] = new ColumnRef(0, $group);
        }
    }

    /**
     * Reconstruct the original format for getRawState() compatibility.
     */
    public function get(): ?array
    {
        if ($this->group === null) {
            return null;
        }

        $result = [];
        foreach ($this->group as $ref) {
            $result[] = $ref->arg->getValue();
        }
        return $result;
    }

    public function reset(): void
    {
        $this->group = null;
    }
}
