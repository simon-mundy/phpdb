<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Closure;
use Override;
use PhpDb\Sql\Part\From;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Predicate\PredicateInterface;

use function array_filter;
use function array_key_exists;
use function implode;
use function strtolower;

/**
 * @property Where $where
 */
class Delete extends AbstractPreparableSql
{
    protected bool $emptyWhereProtection = true;

    protected From $table;

    protected ?Where $where = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new From();

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
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            ($this->where ??= new Where())->addPredicates($predicate, $combination);
        }
        return $this;
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table'                => $this->table->get(),
            'where'                => $this->where ??= new Where(),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    protected function getStatementKeyword(): string
    {
        return 'DELETE';
    }

    #[Override]
    public function buildSqlString(AbstractSqlRenderer $renderer): string
    {
        $renderer->getTypeDecorator($this)?->prepare($this, $renderer);

        $sql = implode(' ', array_filter([
            $this->table->toSql($renderer),
            $this->where?->toSql($renderer),
        ]));

        return $this->getStatementKeyword() . " $sql";
    }

    /**
     * Property overloading
     * Overloads "where" only.
     */
    public function __get(string $name): ?Where
    {
        if (strtolower($name) === 'where') {
            return $this->where ??= new Where();
        }

        return null;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
    }
}
