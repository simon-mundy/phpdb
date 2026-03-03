<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function implode;
use function is_array;

class GroupBy extends AbstractPart
{
    /** @var ColumnRef[]|null */
    private ?array $group = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->group === null) {
            return null;
        }

        $platform = $renderer->platform;
        $groups   = [];

        foreach ($this->group as $ref) {
            $column = $ref->column;

            if ($column instanceof Identifier) {
                $groups[] = $platform->quoteIdentifier($column->identifier);
            } elseif ($column instanceof ArgumentInterface) {
                $pi       = 0;
                $groups[] = $column->render($renderer, '', $pi);
            } else {
                $groups[] = $renderer->render($column);
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
            $result[] = $ref->column instanceof ArgumentInterface
                ? $ref->column->getValue()
                : $ref->column;
        }
        return $result;
    }

    public function reset(): static
    {
        $this->group = null;
        return $this;
    }
}
