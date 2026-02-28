<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Floating;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Floating::class, 'toSql')]
final class FloatingTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Floating('foo', 10, 5);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"foo" FLOAT(10,5) NOT NULL', $sql);
    }
}
