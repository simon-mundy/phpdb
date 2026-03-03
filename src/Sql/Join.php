<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use Override;
use PhpDb\Sql\Part\AbstractPart;
use PhpDb\Sql\Part\JoinSpec;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use ReturnTypeWillChange;

use function count;
use function implode;

/**
 * Aggregate JOIN specifications.
 * Each specification is an array with the following keys:
 * - name: the JOIN table (TableIdentifier)
 * - on: the JOIN condition
 * - type: the type of JOIN being performed; see the `JOIN_*` constants;
 *   defaults to `JOIN_INNER`
 */
class Join extends AbstractPart implements Iterator, Countable
{
    final public const JOIN_INNER = 'INNER';

    final public const JOIN_OUTER = 'OUTER';

    final public const JOIN_FULL_OUTER = 'FULL OUTER';

    final public const JOIN_LEFT = 'LEFT';

    final public const JOIN_RIGHT = 'RIGHT';

    final public const JOIN_RIGHT_OUTER = 'RIGHT OUTER';

    final public const JOIN_LEFT_OUTER = 'LEFT OUTER';

    private int $position = 0;

    /** @var JoinSpec[] */
    private array $specs = [];

    #[Override]
    #[ReturnTypeWillChange]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function current(): array
    {
        $spec = $this->specs[$this->position];
        return ['name' => $spec->table, 'on' => $spec->on, 'type' => $spec->type];
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function key(): int
    {
        return $this->position;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function next(): void
    {
        ++$this->position;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function valid(): bool
    {
        return isset($this->specs[$this->position]);
    }

    public function getJoins(): array
    {
        $result = [];
        foreach ($this->specs as $spec) {
            $result[] = [
                'name' => $spec->table,
                'on'   => $spec->on,
                'type' => $spec->type,
            ];
        }
        return $result;
    }

    public function add(JoinSpec $spec): static
    {
        $this->specs[] = $spec;
        return $this;
    }

    /**
     * Reset to an empty list of JOIN specifications.
     */
    public function reset(): static
    {
        $this->specs = [];
        return $this;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->specs);
    }

    #[Override]
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->specs === []) {
            return null;
        }

        $joinSqlParts = [];

        foreach ($this->specs as $j => $spec) {
            $table = $renderer->resolveTable($spec->table, withAlias: true);

            if (! $spec->isExpressionOn) {
                $onClause = $renderer->quoteIdentifiersIn($spec->on);
            } else {
                $onClause = $renderer->render($spec->on, 'join' . ($j + 1) . 'part');
            }

            $joinSqlParts[] = "{$spec->type} JOIN {$table} ON {$onClause}";
        }

        return implode(' ', $joinSqlParts);
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->specs === [];
    }

    /** @return JoinSpec[] */
    public function getSpecs(): array
    {
        return $this->specs;
    }
}
