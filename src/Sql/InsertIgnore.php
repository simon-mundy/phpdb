<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class InsertIgnore extends Insert
{
    protected function getStatementKeyword(): string
    {
        return 'INSERT IGNORE INTO';
    }
}
