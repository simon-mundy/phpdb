<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Platform\AbstractSqlRenderer;

interface ArgumentInterface
{
    public function getType(): ArgumentType;

    public function getValue(): ExpressionInterface|SqlInterface|string|int|float|bool|array|null;

    public function render(AbstractSqlRenderer $renderer, string $paramPrefix, int &$paramIndex): string;
}
