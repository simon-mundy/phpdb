<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use function explode;
use function implode;
use function is_array;
use function is_string;

class GroupBy extends AbstractPart
{
    /** @var ColumnRef[]|null */
    private ?array $group = null;

    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->group === null) {
            return null;
        }

        $platform = $processor->platform;
        $groups   = [];

        foreach ($this->group as $ref) {
            $column = $ref->column;

            if (is_string($column)) {
                $parts    = explode('.', $column, 2);
                $groups[] = $platform->quoteIdentifier($parts[0], $parts[1] ?? null);
            } else {
                $groups[] = $processor->renderExpression($column);
            }
        }

        return 'GROUP BY ' . implode(', ', $groups);
    }

    public function isEmpty(): bool
    {
        return $this->group === null;
    }

    public function add(mixed $group): static
    {
        if (is_array($group)) {
            foreach ($group as $g) {
                $this->group[] = new ColumnRef(0, $g);
            }
        } else {
            $this->group[] = new ColumnRef(0, $group);
        }
        return $this;
    }

    public function get(): ?array
    {
        if ($this->group === null) {
            return null;
        }

        $result = [];
        foreach ($this->group as $ref) {
            $result[] = $ref->column;
        }
        return $result;
    }

    public function reset(): static
    {
        $this->group = null;
        return $this;
    }
}
