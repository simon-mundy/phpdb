<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\Joins as JoinsPart;
use PhpDb\Sql\Part\Set as SetPart;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Part\Where as WherePart;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function strtolower;

/**
 * @property Where $where
 */
class Update extends AbstractPreparableSql
{
    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    protected bool $emptyWhereProtection = true;

    protected Table $table;

    protected SetPart $set;

    protected ?WherePart $where = null;

    protected ?JoinsPart $joins = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();
        $this->set   = new SetPart();

        if ($table) {
            $this->table($table);
        }
    }

    /**
     * Specify table for statement
     */
    public function table(TableIdentifier|string|array $table): static
    {
        $this->table->set($table);
        return $this;
    }

    /**
     * Set key/value pairs to update
     *
     * @param  array $values Associative array of key values
     * @param string|int $flag One of the VALUES_* constants or a numeric priority
     * @throws Exception\InvalidArgumentException
     */
    public function set(array $values, string|int $flag = self::VALUES_SET): static
    {
        $this->set->set($values, $flag);
        return $this;
    }

    /**
     * Create where clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        ($this->where ??= new WherePart())->addPredicates($predicate, $combination);
        return $this;
    }

    /**
     * Create join clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function join(array|string|TableIdentifier $name, string $on, string $type = Join::JOIN_INNER): static
    {
        ($this->joins ??= new JoinsPart())->join($name, $on, [], $type);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $where = $this->where ??= new WherePart();
        $joins = $this->joins ??= new JoinsPart();

        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'set'                  => $this->set->toArray(),
            'where'                => $where->model ??= new Where(),
            'joins'                => $joins->model ??= new Join(),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Get the statement keyword (e.g. "UPDATE").
     * Override in subclasses for variants like "UPDATE IGNORE".
     */
    protected function getStatementKeyword(): string
    {
        return 'UPDATE';
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        if ($this instanceof PlatformDecoratorInterface) {
            $this->localizeVariables();
            $decorator = $this;
        } else {
            $decorator = null;
        }

        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $decorator);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        // Render inline: UPDATE table [JOINS] SET ... [WHERE ...]
        $sql = $this->getStatementKeyword() . ' ' . $this->table->toSql($processor);

        if ($this->joins !== null) {
            $joinsSql = $this->joins->toSql($processor);
            if ($joinsSql !== null) {
                $sql .= ' ' . $joinsSql;
            }
        }

        $setSql = $this->set->toSql($processor);
        if ($setSql !== null) {
            $sql .= ' ' . $setSql;
        }

        if ($this->where !== null) {
            $whereSql = $this->where->toSql($processor);
            if ($whereSql !== null) {
                $sql .= ' ' . $whereSql;
            }
        }

        return $sql;
    }

    /**
     * Variable overloading
     * Proxies to "where" only
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            $where                 = $this->where ??= new WherePart();
            return $where->model ??= new Where();
        }

        return null;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->set   = clone $this->set;
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
        if ($this->joins !== null) {
            $this->joins = clone $this->joins;
        }
    }
}
