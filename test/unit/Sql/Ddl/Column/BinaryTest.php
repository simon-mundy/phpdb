<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Binary;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Binary::class, 'renderSql')]
final class BinaryTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Binary('foo', 10000000);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" BINARY(10000000) NOT NULL', $sql);
    }
}
