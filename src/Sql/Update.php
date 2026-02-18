<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\Joins as JoinsPart;
use PhpDb\Sql\Part\Literal;
use PhpDb\Sql\Part\PartInterface;
use PhpDb\Sql\Part\Set as SetPart;
use PhpDb\Sql\Part\SqlPartProcessor;
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

    protected WherePart $where;

    protected JoinsPart $joins;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();
        $this->set   = new SetPart();
        $this->where = new WherePart();
        $this->joins = new JoinsPart();

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
        $this->where->addPredicates($predicate, $combination);
        return $this;
    }

    /**
     * Create join clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function join(array|string|TableIdentifier $name, string $on, string $type = Join::JOIN_INNER): static
    {
        $this->joins->join($name, $on, [], $type);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'set'                  => $this->set->toArray(),
            'where'                => $this->where->getModel(),
            'joins'                => $this->joins->getModel(),
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

    /** @return PartInterface[] */
    protected function getParts(): array
    {
        return [
            new Literal($this->getStatementKeyword()),
            $this->table,
            $this->joins,
            $this->set,
            $this->where,
        ];
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
     * Proxies to "where" only
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->where->getModel();
        }

        return null;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->set   = clone $this->set;
        $this->where = clone $this->where;
        $this->joins = clone $this->joins;
    }
}
