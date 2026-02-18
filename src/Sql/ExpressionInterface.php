<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Part\SqlProcessor;

interface ExpressionInterface
{
    /**
     * Render this expression directly to a SQL string.
     *
     * Implementations should render their own structure using $processor->renderArgument()
     * for each argument.
     */
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string;
}
