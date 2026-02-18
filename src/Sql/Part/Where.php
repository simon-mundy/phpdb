<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Closure;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDb\Sql\Where as WhereModel;

/**
 * Wraps a Where predicate model and renders it as a WHERE clause.
 * Used by Select, Update, and Delete.
 */
class Where extends AbstractPart
{
    private ?WhereModel $where = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->where === null || $this->where->count() === 0) {
            return null;
        }

        return 'WHERE ' . $processor->processExpression($this->where, 'where');
    }

    public function isEmpty(): bool
    {
        return $this->where === null || $this->where->count() === 0;
    }

    /**
     * Add predicates to the where clause, or replace with a Where instance.
     */
    public function addPredicates(
        PredicateInterface|WhereModel|array|Closure|string $predicate,
        string $combination = PredicateSet::OP_AND
    ): void {
        if ($predicate instanceof WhereModel) {
            $this->where = $predicate;
        } else {
            $this->getModel()->addPredicates($predicate, $combination);
        }
    }

    /**
     * Get the underlying Where model, creating it lazily.
     */
    public function getModel(): WhereModel
    {
        return $this->where ??= new WhereModel();
    }

    /**
     * Replace the underlying Where model.
     */
    public function setModel(?WhereModel $where): void
    {
        $this->where = $where;
    }

    public function __clone()
    {
        if ($this->where !== null) {
            $this->where = clone $this->where;
        }
    }
}
