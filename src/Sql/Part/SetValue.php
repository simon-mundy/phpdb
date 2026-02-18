<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentInterface;

/**
 * Normalized UPDATE SET column=value pair.
 * The value is stored as an ArgumentInterface (Parameter for scalars, Select for expressions/subqueries).
 */
final readonly class SetValue
{
    public function __construct(
        public string $column,
        public ArgumentInterface $value,
    ) {
    }
}
