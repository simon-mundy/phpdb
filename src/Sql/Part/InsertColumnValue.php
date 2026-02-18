<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentInterface;

/**
 * Normalized INSERT column=value pair.
 * The value is stored as an ArgumentInterface (Parameter for scalars, Select for expressions/subqueries).
 */
final readonly class InsertColumnValue
{
    public function __construct(
        public string $column,
        public ArgumentInterface $value,
    ) {
    }
}
