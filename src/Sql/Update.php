<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\From;
use PhpDb\Sql\Part\Set as SetPart;
use PhpDb\Sql\Part\SqlFragment;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_key_exists;
use function strtolower;

/**
 * @property Where $where
 * @property Join $joins
 */
class Update extends AbstractPreparableSql
{
    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    protected bool $emptyWhereProtection = true;

    protected From $table;

    protected SetPart $set;

    protected ?Where $where = null;

    protected ?Join $joins = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new From();
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
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            ($this->where ??= new Where())->addPredicates($predicate, $combination);
        }
        return $this;
    }

    /**
     * Create join clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        string $type = Join::JOIN_INNER
    ): static {
        ($this->joins ??= new Join())->add(Table::createJoinSpec($name, $on, [], $type));
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'set'                  => $this->set->toArray(),
            'where'                => $this->where ??= new Where(),
            'joins'                => $this->joins ?? new Join(),
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
        ?ParameterContainer $parameterContainer = null,
        ?SqlPlatform $sqlPlatform = null,
    ): string {
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $sqlPlatform);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);
        $sqlPlatform?->getTypeDecorator($this)?->prepare($this, $processor);

        return (string) SqlFragment::of($this->getStatementKeyword())
            ->part($this->table->renderTable($processor))
            ->part($this->joins?->toSql($processor))
            ->part($this->set->toSql($processor))
            ->part($this->where?->toSql($processor));
    }

    /**
     * Variable overloading
     */
    public function __get(string $name): Where|Join|null
    {
        switch (strtolower($name)) {
            case 'where':
                return $this->where ??= new Where();
            case 'joins':
                return $this->joins ?? new Join();
            default:
                return null;
        }
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
