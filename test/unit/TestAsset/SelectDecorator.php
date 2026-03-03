<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Platform\SqlDecoratorInterface;

final class SelectDecorator implements SqlDecoratorInterface
{
    public function prepare(object $subject, AbstractSqlRenderer $renderer): void
    {
    }
}
