<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Part\SqlProcessor;

interface ExpressionInterface
{
    /**
     * Returns raw expression data as array for optimised processing.
     *
     * @return array{spec: string, values: ArgumentInterface[]}
     */
    public function getExpressionData(): array;

    /**
     * Render this expression directly to a SQL string.
     *
     * Implementations should render their own structure using $processor->renderArgument()
     * for each argument. The default fallback delegates to $processor->processExpression().
     */
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string;
}
