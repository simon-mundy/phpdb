<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Closure;
use PhpDb\Sql\Having as HavingModel;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Predicate\PredicateSet;

/**
 * Wraps a Having predicate model and renders it as a HAVING clause.
 */
class Having extends AbstractPart
{
    private ?HavingModel $having = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->having === null || $this->having->count() === 0) {
            return null;
        }

        return 'HAVING ' . $processor->processExpression($this->having, 'having');
    }

    public function isEmpty(): bool
    {
        return $this->having === null || $this->having->count() === 0;
    }

    /**
     * Add predicates to the having clause, or replace with a Having instance.
     */
    public function addPredicates(
        PredicateInterface|HavingModel|array|Closure|string $predicate,
        string $combination = PredicateSet::OP_AND
    ): void {
        if ($predicate instanceof HavingModel) {
            $this->having = $predicate;
        } else {
            $this->getModel()->addPredicates($predicate, $combination);
        }
    }

    public function getModel(): HavingModel
    {
        return $this->having ??= new HavingModel();
    }

    public function setModel(?HavingModel $having): void
    {
        $this->having = $having;
    }

    public function __clone()
    {
        if ($this->having !== null) {
            $this->having = clone $this->having;
        }
    }
}
