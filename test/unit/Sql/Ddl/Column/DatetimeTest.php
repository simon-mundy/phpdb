<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Datetime;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Datetime::class, 'toSql')]
final class DatetimeTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Datetime('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" DATETIME NOT NULL', $sql);
    }
}
