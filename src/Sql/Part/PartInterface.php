<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Stringable;

interface PartInterface extends Stringable
{
    /**
     * Render this part to a SQL string fragment.
     * Returns null if this part should be omitted from the final statement.
     */
    public function toSql(SqlProcessor $processor): ?string;

    /**
     * Whether this part currently has no content to render.
     */
    public function isEmpty(): bool;
}
