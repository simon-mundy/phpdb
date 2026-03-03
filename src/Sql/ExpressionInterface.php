<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Platform\AbstractSqlRenderer;

interface ExpressionInterface
{
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string;
}
