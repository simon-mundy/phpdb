<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\Columns;
use PhpDb\Sql\Part\Combine as CombinePart;
use PhpDb\Sql\Part\GroupBy;
use PhpDb\Sql\Part\Having as HavingPart;
use PhpDb\Sql\Part\Joins;
use PhpDb\Sql\Part\Limit;
use PhpDb\Sql\Part\Literal;
use PhpDb\Sql\Part\Offset;
use PhpDb\Sql\Part\OrderBy;
use PhpDb\Sql\Part\PartInterface;
use PhpDb\Sql\Part\Quantifier;
use PhpDb\Sql\Part\SelectClause;
use PhpDb\Sql\Part\SqlPartProcessor;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Part\Where as WherePart;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function count;
use function gettype;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function key;
use function sprintf;
use function strtolower;

/**
 * @property Where $where
 * @property Having $having
 * @property Join $joins
 */
class Select extends AbstractPreparableSql
{
    /**#@+
     * Constant
     *
     * @const
     */
    final public const SELECT = 'select';

    final public const QUANTIFIER = 'quantifier';

    final public const COLUMNS = 'columns';

    final public const TABLE = 'table';

    final public const JOINS = 'joins';

    final public const WHERE = 'where';

    final public const GROUP = 'group';

    final public const HAVING = 'having';

    final public const ORDER = 'order';

    final public const LIMIT = 'limit';

    final public const OFFSET = 'offset';

    final public const QUANTIFIER_DISTINCT = 'DISTINCT';

    final public const QUANTIFIER_ALL = 'ALL';

    final public const JOIN_INNER = Join::JOIN_INNER;

    final public const JOIN_OUTER = Join::JOIN_OUTER;

    final public const JOIN_FULL_OUTER = Join::JOIN_FULL_OUTER;

    final public const JOIN_LEFT = Join::JOIN_LEFT;

    final public const JOIN_RIGHT = Join::JOIN_RIGHT;

    final public const JOIN_RIGHT_OUTER = Join::JOIN_RIGHT_OUTER;

    final public const JOIN_LEFT_OUTER = Join::JOIN_LEFT_OUTER;

    final public const SQL_STAR = '*';

    final public const ORDER_ASCENDING = 'ASC';

    final public const ORDER_DESCENDING = 'DESC';

    final public const COMBINE = 'combine';

    final public const COMBINE_UNION = 'union';

    final public const COMBINE_EXCEPT = 'except';

    final public const COMBINE_INTERSECT = 'intersect';

    protected bool $tableReadOnly = false;

    protected Table $table;

    protected Quantifier $quantifier;

    protected Columns $columns;

    protected Joins $joins;

    protected WherePart $where;

    protected GroupBy $groupBy;

    protected HavingPart $having;

    protected OrderBy $orderBy;

    protected Limit $limit;

    protected Offset $offset;

    protected CombinePart $combine;

    /**
     * Constructor
     */
    public function __construct(array|string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();
        $this->quantifier = new Quantifier();
        $this->columns = new Columns();
        $this->joins = new Joins();
        $this->where = new WherePart();
        $this->groupBy = new GroupBy();
        $this->having = new HavingPart();
        $this->orderBy = new OrderBy();
        $this->limit = new Limit();
        $this->offset = new Offset();
        $this->combine = new CombinePart();

        if ($table) {
            $this->from($table);
            $this->tableReadOnly = true;
        }
    }

    /**
     * Create from clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function from(array|string|TableIdentifier $table): static
    {
        if ($this->tableReadOnly) {
            throw new Exception\InvalidArgumentException(
                'Since this object was created with a table and/or schema in the constructor, it is read only.'
            );
        }

        if (is_array($table) && (! is_string(key($table)) || count($table) !== 1)) {
            throw new Exception\InvalidArgumentException(
                'from() expects $table as an array is a single element associative array'
            );
        }

        $this->table->set($table);
        return $this;
    }

    /**
     * @param string|Expression $quantifier DISTINCT|ALL
     * @throws Exception\InvalidArgumentException
     */
    public function quantifier(ExpressionInterface|string $quantifier): static
    {
        $this->quantifier->set($quantifier);
        return $this;
    }

    /**
     * Specify columns from which to select
     * Possible valid states:
     *   array(*)
     *   array(value, ...)
     *     value can be strings or Expression objects
     *   array(string => value, ...)
     *     key string will be use as alias,
     *     value can be string or Expression objects
     */
    public function columns(array $columns, bool $prefixColumnsWithTable = true): static
    {
        $this->columns->set($columns);
        $this->columns->setPrefixColumnsWithTable($prefixColumnsWithTable);
        return $this;
    }

    /**
     * Create join clause
     *
     * @param string                    $type one of the JOIN_* constants
     * @throws Exception\InvalidArgumentException
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = self::SQL_STAR,
        string $type = self::JOIN_INNER
    ): static {
        $this->joins->join($name, $on, $columns, $type);

        return $this;
    }

    /**
     * Create where clause
     *
     * @param string                                  $combination One of the OP_* constants from Predicate\PredicateSet
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|string|Closure $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): self {
        $this->where->addPredicates($predicate, $combination);

        return $this;
    }

    public function group(mixed $group): static
    {
        $this->groupBy->add($group);

        return $this;
    }

    /**
     * Create having clause
     *
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     */
    public function having(
        Having|PredicateInterface|array|Closure|string $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        $this->having->addPredicates($predicate, $combination);

        return $this;
    }

    public function order(ExpressionInterface|array|string $order): static
    {
        $this->orderBy->add($order);

        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function limit(int|string $limit): static
    {
        if (! is_numeric($limit)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects parameter to be numeric, "%s" given',
                __METHOD__,
                gettype($limit)
            ));
        }

        $this->limit->set($limit);
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function offset(int|string $offset): static
    {
        if (! is_numeric($offset)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects parameter to be numeric, "%s" given',
                __METHOD__,
                gettype($offset)
            ));
        }

        $this->offset->set($offset);
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if (! $this->combine->isEmpty()) {
            throw new Exception\InvalidArgumentException(
                'This Select object is already combined and cannot be combined with multiple Selects objects'
            );
        }

        $this->combine->set($select, $type, $modifier);
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function reset(string $part): static
    {
        switch ($part) {
            case self::TABLE:
                if ($this->tableReadOnly) {
                    throw new Exception\InvalidArgumentException(
                        'Since this object was created with a table and/or schema in the constructor, it is read only.'
                    );
                }

                $this->table->set(null);
                break;
            case self::QUANTIFIER:
                $this->quantifier->set(null);
                break;
            case self::COLUMNS:
                $this->columns->set([]);
                break;
            case self::JOINS:
                $this->joins->setModel(null);
                break;
            case self::WHERE:
                $this->where->setModel(null);
                break;
            case self::GROUP:
                $this->groupBy->reset();
                break;
            case self::HAVING:
                $this->having->setModel(null);
                break;
            case self::LIMIT:
                $this->limit->set(null);
                break;
            case self::OFFSET:
                $this->offset->set(null);
                break;
            case self::ORDER:
                $this->orderBy->reset();
                break;
            case self::COMBINE:
                $this->combine->reset();
                break;
        }

        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            self::TABLE      => $this->table->get(),
            self::QUANTIFIER => $this->quantifier->get(),
            self::COLUMNS    => $this->columns->get(),
            self::JOINS      => $this->joins->getModel(),
            self::WHERE      => $this->where->getModel(),
            self::ORDER      => $this->orderBy->get(),
            self::GROUP      => $this->groupBy->get(),
            self::HAVING     => $this->having->getModel(),
            self::LIMIT      => $this->limit->get(),
            self::OFFSET     => $this->offset->get(),
            self::COMBINE    => $this->combine->get(),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Returns whether the table is read only or not.
     */
    public function isTableReadOnly(): bool
    {
        return $this->tableReadOnly;
    }

    /** @return PartInterface[] */
    protected function getParts(): array
    {
        $parts = [];

        // Statement start paren (when combined)
        if (! $this->combine->isEmpty()) {
            $parts[] = new Literal('(');
        }

        // SELECT [QUANTIFIER] columns [FROM table]
        $parts[] = new SelectClause($this->quantifier, $this->columns, $this->table);

        // JOINS
        $parts[] = $this->joins;

        // WHERE
        $parts[] = $this->where;

        // GROUP BY
        $parts[] = $this->groupBy;

        // HAVING
        $parts[] = $this->having;

        // ORDER BY
        $parts[] = $this->orderBy;

        // LIMIT
        $parts[] = $this->limit;

        // OFFSET
        $parts[] = $this->offset;

        // Statement end paren (when combined)
        if (! $this->combine->isEmpty()) {
            $parts[] = new Literal(')');
        }

        // COMBINE (UNION/EXCEPT/INTERSECT)
        $parts[] = $this->combine;

        return $parts;
    }

    /**
     * Prepare the column parts for building by resolving the table prefix and join column info.
     */
    protected function preparePartsForBuild(SqlPartProcessor $processor): void
    {
        // Resolve the table prefix for column prefixing
        if ($this->columns->getPrefixColumnsWithTable() && $this->table->get() !== null) {
            $fromTablePrefix = $this->table->getQuotedPrefix($processor);
        } else {
            $fromTablePrefix = '';
        }

        $this->columns->setFromTablePrefix($fromTablePrefix);

        // Resolve join column info for column rendering
        $joinColumnInfo = [];
        $joinsModel = $this->joins->getModel();
        foreach ($joinsModel->getJoins() as $join) {
            $joinName = is_array($join['name']) ? key($join['name']) : $join['name'];
            $resolvedJoinName = $processor->resolveTable($joinName);

            $joinColumnInfo[] = [
                'name'    => $resolvedJoinName,
                'columns' => $join['columns'],
            ];
        }

        $this->columns->setJoinColumnInfo($joinColumnInfo);
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $decorator = $this instanceof PlatformDecoratorInterface ? $this : null;
        $processor = new SqlPartProcessor($platform, $driver, $parameterContainer, $decorator);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        $this->preparePartsForBuild($processor);

        $sqls = [];
        foreach ($this->getParts() as $part) {
            $sql = $part->toSql($processor);
            if ($sql !== null) {
                $sqls[] = $sql;
            }
        }

        return implode(' ', $sqls);
    }

    /**
     * Variable overloading
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): Where|Join|Having
    {
        return match (strtolower($name)) {
            'where' => $this->where->getModel(),
            'having' => $this->having->getModel(),
            'joins' => $this->joins->getModel(),
            default => throw new Exception\InvalidArgumentException('Not a valid magic property for this object'),
        };
    }

    /**
     * __clone
     *
     * Resets the where object each time the Select is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        $this->table = clone $this->table;
        $this->quantifier = clone $this->quantifier;
        $this->columns = clone $this->columns;
        $this->joins = clone $this->joins;
        $this->where = clone $this->where;
        $this->groupBy = clone $this->groupBy;
        $this->having = clone $this->having;
        $this->orderBy = clone $this->orderBy;
        $this->limit = clone $this->limit;
        $this->offset = clone $this->offset;
        $this->combine = clone $this->combine;
    }
}
