<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Closure;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDb\Sql\Where as WhereModel;

class Where extends AbstractPart
{
    public ?WhereModel $model = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->model === null || $this->model->count() === 0) {
            return null;
        }

        return 'WHERE ' . $processor->renderExpression($this->model, 'where');
    }

    public function isEmpty(): bool
    {
        return $this->model === null || $this->model->count() === 0;
    }

    public function addPredicates(
        PredicateInterface|WhereModel|array|Closure|string $predicate,
        string $combination = PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof WhereModel) {
            $this->model = $predicate;
        } else {
            $this->model ??= new WhereModel();
            $this->model->addPredicates($predicate, $combination);
        }
        return $this;
    }

    public function __clone()
    {
        if ($this->model !== null) {
            $this->model = clone $this->model;
        }
    }
}
