<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
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
class Delete extends AbstractPreparableSql
{
    protected bool $emptyWhereProtection = true;

    protected Table $table;

    protected ?WherePart $where = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();

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
        ($this->where ??= new WherePart())->addPredicates($predicate, $combination);
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $where = $this->where ??= new WherePart();

        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'where'                => $where->model ??= new Where(),
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

        // Render inline: DELETE FROM table [WHERE ...]
        $sql = $this->getStatementKeyword() . ' ' . $this->table->toSql($processor);

        if ($this->where !== null) {
            $whereSql = $this->where->toSql($processor);
            if ($whereSql !== null) {
                $sql .= ' ' . $whereSql;
            }
        }

        return $sql;
    }

    /**
     * Property overloading
     * Overloads "where" only.
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            $where = $this->where ??= new WherePart();
            return $where->model ??= new Where();
        }

        return null;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        if ($this->where !== null) $this->where = clone $this->where;
    }
}
