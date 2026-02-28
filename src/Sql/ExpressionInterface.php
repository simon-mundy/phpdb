<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Part\SqlProcessor;

interface ExpressionInterface
{
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string;
}
