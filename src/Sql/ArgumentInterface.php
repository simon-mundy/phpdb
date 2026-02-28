<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Part\SqlProcessor;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): ExpressionInterface|SqlInterface|string|int|float|bool|array|null;

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string;
}
