<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Stringable;

interface PartInterface extends Stringable
{
    public function toSql(SqlProcessor $processor): ?string;

    public function isEmpty(): bool;
}
