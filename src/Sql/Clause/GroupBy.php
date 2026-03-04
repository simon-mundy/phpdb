<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function implode;
use function is_array;
use function is_string;
use function str_replace;

class GroupBy extends AbstractClause
{
    /** @var list<ArgumentInterface|ExpressionInterface>|null */
    private ?array $group = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->group === null) {
            return null;
        }

        $groups = [];

        foreach ($this->group as $column) {
            if ($column instanceof Identifier) {
                $groups[] = $renderer->qo . str_replace('.', $renderer->qs, $column->identifier) . $renderer->qc;
            } elseif ($column instanceof ArgumentInterface) {
                $pi       = 0;
                $groups[] = $renderer->renderArgument($column, '', $pi);
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
                $this->group[] = is_string($g) ? new Identifier($g) : $g;
            }
        } else {
            $this->group[] = is_string($group) ? new Identifier($group) : $group;
        }
        return $this;
    }

    public function get(): ?array
    {
        if ($this->group === null) {
            return null;
        }

        $result = [];
        foreach ($this->group as $column) {
            $result[] = $column instanceof ArgumentInterface
                ? $column->getValue()
                : $column;
        }
        return $result;
    }

    public function reset(): static
    {
        $this->group = null;
        return $this;
    }
}
