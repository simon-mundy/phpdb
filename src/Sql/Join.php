<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use Override;
use PhpDb\Sql\Part\AbstractPart;
use PhpDb\Sql\Part\JoinSpec;
use PhpDb\Sql\Part\SqlProcessor;
use ReturnTypeWillChange;

use function array_shift;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function key;
use function preg_replace_callback;
use function spl_object_id;
use function sprintf;

/**
 * Aggregate JOIN specifications.
 * Each specification is an array with the following keys:
 * - name: the JOIN name
 * - on: the table on which the JOIN occurs
 * - columns: the columns to include with the JOIN operation; defaults to
 *   `Select::SQL_STAR`.
 * - type: the type of JOIN being performed; see the `JOIN_*` constants;
 *   defaults to `JOIN_INNER`
 */
class Join extends AbstractPart implements Iterator, Countable
{
    private const IDENTIFIER_PATTERN
        = '/\b(?!(?:AS|AND|OR|BETWEEN)\b)([a-zA-Z_]\w*+(?:\.[a-zA-Z_]\w*+)*)(?!\s*\()/i';

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

    private ?string $sqlCache        = null;
    private ?int $sqlCachePlatformId = null;

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
        return $this->specs[$this->position]->raw;
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
        $raw = [];
        foreach ($this->specs as $spec) {
            $raw[] = $spec->raw;
        }
        return $raw;
    }

    /**
     * @param array|string|TableIdentifier $name    A table name on which to join, or a single
     *     element associative array, of the form alias => table, or TableIdentifier instance
     * @param string|Predicate\Expression  $on      A specification describing the fields to join on.
     * @param int|string|int[]|string[]    $columns A single column name, an array
     *     of column names, or (a) specification(s) such as SQL_STAR representing
     *     the columns to join.
     * @param string                       $type    The JOIN type to use; see the JOIN_* constants.
     * @throws Exception\InvalidArgumentException For invalid $name values.
     */
    // phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
    public function join(
        array|string|TableIdentifier $name,
        string|Predicate\PredicateInterface $on,
        array|int|string $columns = [Select::SQL_STAR],
        string $type = self::JOIN_INNER
    ): static {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            throw new Exception\InvalidArgumentException(
                sprintf("join() expects '%s' as a single element associative array", array_shift($name))
            );
        }

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $this->specs[]  = new JoinSpec([
            'name'    => $name,
            'on'      => $on,
            'columns' => $columns,
            'type'    => $type,
        ]);
        $this->sqlCache = null;

        return $this;
    }

    /**
     * Reset to an empty list of JOIN specifications.
     */
    public function reset(): static
    {
        $this->specs              = [];
        $this->sqlCache           = null;
        $this->sqlCachePlatformId = null;
        return $this;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->specs);
    }

    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->specs === []) {
            return null;
        }

        $canCache = $processor->parameterContainer === null;
        if ($canCache) {
            $platformId = spl_object_id($processor->platform);
            if ($this->sqlCachePlatformId === $platformId) {
                return $this->sqlCache;
            }
        }

        $platform     = $processor->platform;
        $joinSqlParts = [];

        foreach ($this->specs as $j => $spec) {
            $renderedTable = $spec->table->resolveTableWithAlias($processor);

            if ($spec->isExpressionOn) {
                $onClause = $processor->renderExpression(
                    $spec->on,
                    'join' . ($j + 1) . 'part'
                );
            } else {
                $onClause = preg_replace_callback(
                    self::IDENTIFIER_PATTERN,
                    static fn($m) => $platform->quoteIdentifier(...explode('.', $m[1], 2)),
                    $spec->on,
                );
            }

            $joinSqlParts[] = "{$spec->type} JOIN {$renderedTable} ON {$onClause}";
        }

        $result = implode(' ', $joinSqlParts);

        if ($canCache) {
            $this->sqlCache           = $result;
            $this->sqlCachePlatformId = $platformId;
        }

        return $result;
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

    public function __clone()
    {
        $this->sqlCache           = null;
        $this->sqlCachePlatformId = null;
    }
}
