<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Insert;

class Replace extends Insert
{
    protected function getStatementKeyword(): string
    {
        return 'REPLACE INTO';
    }
}
