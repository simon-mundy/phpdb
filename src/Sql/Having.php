<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Part\PartInterface;
use PhpDb\Sql\Part\SqlProcessor;

class Having extends Predicate\Predicate implements PartInterface
{
    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->count() === 0) {
            return null;
        }

        if ($paramPrefix !== '') {
            return parent::toSql($processor, $paramPrefix, $paramIndex);
        }

        if ($processor->parameterContainer !== null) {
            return 'HAVING ' . $processor->renderExpression($this, 'having');
        }

        $pi = 0;
        return 'HAVING ' . parent::toSql($processor, '', $pi);
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
