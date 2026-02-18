<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_array;

/**
 * Normalized table reference with optional alias.
 * Alias extraction from array format happens at construction time.
 */
final readonly class TableRef
{
    public string|TableIdentifier|Select $table;
    public ?string $alias;

    public function __construct(string|array|TableIdentifier|Select $table)
    {
        if (is_array($table)) {
            $this->alias = key($table);
            $this->table = current($table);
        } else {
            $this->alias = null;
            $this->table = $table;
        }
    }
}
