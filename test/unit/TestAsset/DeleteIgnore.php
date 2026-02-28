<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Delete;

class DeleteIgnore extends Delete
{
    protected function getStatementKeyword(): string
    {
        return 'DELETE IGNORE';
    }
}
