<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Closure;
use PhpDb\Sql\Having as HavingModel;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Predicate\PredicateSet;

class Having extends AbstractPart
{
    public ?HavingModel $model = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->model === null || $this->model->count() === 0) {
            return null;
        }

        return 'HAVING ' . $processor->renderExpression($this->model, 'having');
    }

    public function isEmpty(): bool
    {
        return $this->model === null || $this->model->count() === 0;
    }

    public function addPredicates(
        PredicateInterface|HavingModel|array|Closure|string $predicate,
        string $combination = PredicateSet::OP_AND
    ): static {
        if ($predicate instanceof HavingModel) {
            $this->model = $predicate;
        } else {
            $this->model ??= new HavingModel();
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
