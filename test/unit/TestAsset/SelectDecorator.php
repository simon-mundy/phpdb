<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\SqlDecoratorInterface;

final class SelectDecorator implements SqlDecoratorInterface
{
    public function prepare(object $subject, SqlProcessor $processor): void
    {
    }
}
