<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Update;

class UpdateIgnore extends Update
{
    protected function getStatementKeyword(): string
    {
        return 'UPDATE IGNORE';
    }
}
