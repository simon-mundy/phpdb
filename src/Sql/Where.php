<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Part\PartInterface;
use PhpDb\Sql\Part\SqlProcessor;

class Where extends Predicate\Predicate implements PartInterface
{
    #[Override]
    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->count() === 0) {
            return null;
        }

        return 'WHERE ' . $processor->renderExpression($this, 'where');
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function __toString(): string
    {
        return $this->toSql(new SqlProcessor(new Sql92())) ?? '';
    }
}
