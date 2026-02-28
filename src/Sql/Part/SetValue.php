<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentInterface;

final readonly class SetValue
{
    public function __construct(
        public string $column,
        public ArgumentInterface $value,
    ) {
    }
}
