<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

final readonly class NullValue implements ArgumentInterface
{
    public function getType(): ArgumentType
    {
        return ArgumentType::Null;
    }

    public function getValue(): null
    {
        return null;
    }

    public function render(AbstractSqlRenderer $renderer, string $paramPrefix, int &$paramIndex): string
    {
        return 'NULL';
    }
}
