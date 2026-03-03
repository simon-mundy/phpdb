<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Floating;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Floating::class, 'toSql')]
final class FloatingTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Floating('foo', 10, 5);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" FLOAT(10,5) NOT NULL', $sql);
    }
}
