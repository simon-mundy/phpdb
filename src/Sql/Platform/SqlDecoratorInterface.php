<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

interface SqlDecoratorInterface
{
    public function prepare(object $subject, AbstractSqlRenderer $renderer): void;
}
