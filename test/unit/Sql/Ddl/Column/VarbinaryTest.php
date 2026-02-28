<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Varbinary;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varbinary::class, 'toSql')]
final class VarbinaryTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Varbinary('foo', 20);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"foo" VARBINARY(20) NOT NULL', $sql);
    }
}
