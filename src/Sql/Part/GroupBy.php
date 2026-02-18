<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use function implode;
use function is_array;

/**
 * Holds and renders GROUP BY clause.
 */
class GroupBy extends AbstractPart
{
    private ?array $group = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->group === null) {
            return null;
        }

        $groups = [];
        foreach ($this->group as $column) {
            $groups[] = $processor->resolveColumnValue(
                [
                    'column'       => $column,
                    'isIdentifier' => true,
                ],
                'group'
            );
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
                $this->group[] = $g;
            }
        } else {
            $this->group[] = $group;
        }
    }

    public function get(): ?array
    {
        return $this->group;
    }

    public function reset(): void
    {
        $this->group = null;
    }
}
