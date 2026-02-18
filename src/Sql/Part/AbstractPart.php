<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Platform\Sql92;

abstract class AbstractPart implements PartInterface
{
    public function __toString(): string
    {
        return $this->toSql(new SqlPartProcessor(new Sql92())) ?? '';
    }
}
