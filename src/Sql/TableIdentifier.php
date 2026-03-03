<?php

declare(strict_types=1);

namespace PhpDb\Sql;

final readonly class TableIdentifier
{
    public function __construct(
        public string $table,
        public ?string $schema = null,
    ) {
        if ($table === '') {
            throw new Exception\InvalidArgumentException(
                '$table must be a valid table name, empty string given'
            );
        }

        if ($schema !== null && $schema === '') {
            throw new Exception\InvalidArgumentException(
                '$schema must be a valid schema name or null, empty string given'
            );
        }
    }
}
