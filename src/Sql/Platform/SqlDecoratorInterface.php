<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Sql\Part\SqlProcessor;

interface SqlDecoratorInterface
{
    public function prepare(object $subject, SqlProcessor $processor): void;
}
