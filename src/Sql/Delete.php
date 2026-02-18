<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\Literal;
use PhpDb\Sql\Part\PartInterface;
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
class Delete extends AbstractPreparableSql
{
    protected bool $emptyWhereProtection = true;

    protected Table $table;

    protected WherePart $where;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();
        $this->where = new WherePart();

        if ($table) {
            $this->from($table);
        }
    }

    /**
     * Create from statement
     */
    public function from(TableIdentifier|string|array $table): static
    {
        $this->table->set($table);
        return $this;
    }

    /**
     * Create where clause
     *
     * @param string $combination One of the OP_* constants from Predicate\PredicateSet
     */
    public function where(
        PredicateInterface|array|Closure|string|Where $predicate,
        string $combination = Predicate\PredicateSet::OP_AND
    ): static {
        $this->where->addPredicates($predicate, $combination);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'where'                => $this->where->model ??= new Where(),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Get the statement keyword (e.g. "DELETE FROM").
     * Override in subclasses for variants like "DELETE IGNORE FROM".
     */
    protected function getStatementKeyword(): string
    {
        return 'DELETE FROM';
    }

    /** @return PartInterface[] */
    protected function getParts(): array
    {
        return [
            new Literal($this->getStatementKeyword()),
            $this->table,
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
     * Property overloading
     * Overloads "where" only.
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->where->model ??= new Where();
        }

        return null;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->where = clone $this->where;
    }
}
