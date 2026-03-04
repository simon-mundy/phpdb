<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Platform\Sql92Renderer;

abstract class AbstractClause implements ClauseInterface
{
    public function __toString(): string
    {
        return $this->toSql((new Sql92Renderer())->init(new Sql92())) ?? '';
    }
}
