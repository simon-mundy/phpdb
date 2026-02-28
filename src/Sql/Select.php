<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\Combine as CombinePart;
use PhpDb\Sql\Part\GroupBy;
use PhpDb\Sql\Part\Having as HavingPart;
use PhpDb\Sql\Part\Limit;
use PhpDb\Sql\Part\Offset;
use PhpDb\Sql\Part\OrderBy;
use PhpDb\Sql\Part\Quantifier;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Part\Where as WherePart;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function count;
use function gettype;
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

    final public const COMBINE_UNION = 'UNION';

    final public const COMBINE_EXCEPT = 'EXCEPT';

    final public const COMBINE_INTERSECT = 'INTERSECT';

    protected bool $tableReadOnly = false;

    protected Table $table;

    protected ?Quantifier $quantifier = null;

    protected ?WherePart $where = null;

    protected ?GroupBy $groupBy = null;

    protected ?HavingPart $having = null;

    protected ?OrderBy $orderBy = null;

    protected ?Limit $limit = null;

    protected ?Offset $offset = null;

    protected ?CombinePart $combine = null;

    /**
     * Constructor
     */
    public function __construct(array|string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();

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

        $this->table->setFrom($table);
        return $this;
    }

    /**
     * @param string|Expression $quantifier DISTINCT|ALL
     * @throws Exception\InvalidArgumentException
     */
    public function quantifier(ExpressionInterface|string $quantifier): static
    {
        ($this->quantifier ??= new Quantifier())->set($quantifier);
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
        $this->table->setColumns($columns);
        $this->table->setPrefixColumnsWithTable($prefixColumnsWithTable);
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
        $this->table->join($name, $on, $columns, $type);

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
    ): static {
        ($this->where ??= new WherePart())->addPredicates($predicate, $combination);

        return $this;
    }

    public function group(mixed $group): static
    {
        ($this->groupBy ??= new GroupBy())->add($group);

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
        ($this->having ??= new HavingPart())->addPredicates($predicate, $combination);

        return $this;
    }

    public function order(ExpressionInterface|array|string $order): static
    {
        ($this->orderBy ??= new OrderBy())->add($order);

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

        ($this->limit ??= new Limit())->set($limit);
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

        ($this->offset ??= new Offset())->set($offset);
        return $this;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if ($this->combine !== null && ! $this->combine->isEmpty()) {
            throw new Exception\InvalidArgumentException(
                'This Select object is already combined and cannot be combined with multiple Selects objects'
            );
        }

        ($this->combine ??= new CombinePart())->set($select, $type, $modifier);
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

                $this->table->resetFrom();
                break;
            case self::QUANTIFIER:
                $this->quantifier = null;
                break;
            case self::COLUMNS:
                $this->table->resetColumns();
                break;
            case self::JOINS:
                $this->table->resetJoins();
                break;
            case self::WHERE:
                $this->where = null;
                break;
            case self::GROUP:
                $this->groupBy = null;
                break;
            case self::HAVING:
                $this->having = null;
                break;
            case self::LIMIT:
                $this->limit = null;
                break;
            case self::OFFSET:
                $this->offset = null;
                break;
            case self::ORDER:
                $this->orderBy = null;
                break;
            case self::COMBINE:
                $this->combine = null;
                break;
        }

        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $where  = $this->where ??= new WherePart();
        $having = $this->having ??= new HavingPart();

        $joins = $this->table->joins();

        $rawState = [
            self::TABLE      => $this->table->getFrom(),
            self::QUANTIFIER => $this->quantifier?->get(),
            self::COLUMNS    => $this->table->getColumns(),
            self::JOINS      => $joins !== null ? $joins->getModel() : ($this->table->joinModel ??= new Join()),
            self::WHERE      => $where->model  ??= new Where(),
            self::ORDER      => $this->orderBy?->get() ?? [],
            self::GROUP      => $this->groupBy?->get() ?? [],
            self::HAVING     => $having->model ??= new Having(),
            self::LIMIT      => $this->limit?->get(),
            self::OFFSET     => $this->offset?->get(),
            self::COMBINE    => $this->combine?->get() ?? [],
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

    public function tablePart(): Table
    {
        return $this->table;
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?SqlPlatform $sqlPlatform = null,
    ): string {
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $sqlPlatform);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);
        $sqlPlatform?->getTypeDecorator($this)?->prepare($this, $processor);
        $this->table->prepare($processor);

        $sql = 'SELECT';

        if (($part = $this->quantifier?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        $sql .= ' ' . $this->table->columns()->toSql($processor);
        if (($part = $this->table->from()->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->table->joins()?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->where?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->groupBy?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->having?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->orderBy?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->limit?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->offset?->toSql($processor)) !== null) {
            $sql .= ' ' . $part;
        }
        if (($part = $this->combine?->toSql($processor)) !== null) {
            $sql = '( ' . $sql . ' ) ' . $part;
        }

        return $sql;
    }

    /**
     * Variable overloading
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): Where|Join|Having
    {
        switch (strtolower($name)) {
            case 'where':
                $part = $this->where ??= new WherePart();
                return $part->model ??= new Where();
            case 'having':
                $part = $this->having ??= new HavingPart();
                return $part->model ??= new Having();
            case 'joins':
                $joins = $this->table->joins();
                if ($joins !== null) {
                    return $joins->getModel();
                }
                return $this->table->joinModel ??= new Join();
            default:
                throw new Exception\InvalidArgumentException('Not a valid magic property for this object');
        }
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
        if ($this->quantifier !== null) {
            $this->quantifier = clone $this->quantifier;
        }
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
        if ($this->groupBy !== null) {
            $this->groupBy = clone $this->groupBy;
        }
        if ($this->having !== null) {
            $this->having = clone $this->having;
        }
        if ($this->orderBy !== null) {
            $this->orderBy = clone $this->orderBy;
        }
        if ($this->limit !== null) {
            $this->limit = clone $this->limit;
        }
        if ($this->offset !== null) {
            $this->offset = clone $this->offset;
        }
        if ($this->combine !== null) {
            $this->combine = clone $this->combine;
        }
    }
}
