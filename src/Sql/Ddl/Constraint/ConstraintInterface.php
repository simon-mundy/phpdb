<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\ExpressionInterface;

/**
 * @api
 */
interface ConstraintInterface extends ExpressionInterface
{
    /** @return string[] */
    /** @return string[] */
    public function getColumns(): array;
}
