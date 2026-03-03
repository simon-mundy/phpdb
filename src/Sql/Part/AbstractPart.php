<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Platform\Sql92Renderer;

abstract class AbstractPart implements PartInterface
{
    public function __toString(): string
    {
        return $this->toSql((new Sql92Renderer())->init(new Sql92())) ?? '';
    }
}
