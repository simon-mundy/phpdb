<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function key;
use function preg_replace_callback;
use function spl_object_id;

/**
 * Wraps a Join model and renders all JOIN clauses.
 * Used by Select and Update.
 *
 * JoinSpecs are normalized at join() time. The rendering loop uses typed access
 * on JoinSpec — no instanceof/is_array cascades or per-render allocations.
 */
class Joins extends AbstractPart
{
    private const IDENTIFIER_PATTERN = '/\b(?!(?:AS|AND|OR|BETWEEN)\b)([a-zA-Z_]\w*+(?:\.[a-zA-Z_]\w*+)*)(?!\s*\()/i';

    public ?Join $model = null;

    /** @var JoinSpec[] Normalized join specifications, built at join() time */
    private array $specs = [];

    /** @var array[] Raw join data for lazy Join model building */
    private array $rawJoins = [];

    private ?string $sqlCache = null;
    private ?int $sqlCachePlatformId = null;

    public function toSql(SqlProcessor $processor): ?string
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
            $joinName = match ($spec->tableType) {
                JoinTableType::Expression      => $spec->table->getExpression(), // @phpstan-ignore method.nonObject
                JoinTableType::TableIdentifier => $processor->resolveTable($spec->table),
                JoinTableType::Select          => '(' . $processor->processSubSelect($spec->table) . ')',
                JoinTableType::Identifier      => $platform->quoteIdentifier($spec->table),
            };
            $quotedAlias = $spec->alias !== null
                ? $platform->quoteIdentifier($spec->alias)
                : null;

            $renderedTable = $processor->renderTable($joinName, $quotedAlias);

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
            $this->sqlCache = $result;
            $this->sqlCachePlatformId = $platformId;
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        return $this->specs === [];
    }

    /** @return JoinSpec[] */
    public function getSpecs(): array
    {
        return $this->specs;
    }

    /**
     * Add a join specification.
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = Select::SQL_STAR,
        string $type = Join::JOIN_INNER
    ): static {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            throw new \PhpDb\Sql\Exception\InvalidArgumentException(
                \sprintf("join() expects '%s' as a single element associative array", \array_shift($name))
            );
        }
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $raw = ['name' => $name, 'on' => $on, 'columns' => $columns, 'type' => $type];
        $this->specs[]    = new JoinSpec($raw);
        $this->rawJoins[] = $raw;
        $this->model      = null;
        $this->sqlCache   = null;
        return $this;
    }

    public function getModel(): Join
    {
        if ($this->model === null) {
            $this->model = new Join();
            foreach ($this->rawJoins as $raw) {
                $this->model->join($raw['name'], $raw['on'], $raw['columns'], $raw['type']);
            }
        }
        return $this->model;
    }

    public function reset(): static
    {
        $this->model    = null;
        $this->specs    = [];
        $this->rawJoins = [];
        $this->sqlCache = null;
        $this->sqlCachePlatformId = null;
        return $this;
    }

    public function __clone()
    {
        if ($this->model !== null) {
            $this->model = clone $this->model;
        }
        $this->sqlCache = null;
        $this->sqlCachePlatformId = null;
    }
}
