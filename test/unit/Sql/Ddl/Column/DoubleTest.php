<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Double;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Double::class, 'renderSql')]
final class DoubleTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Double('foo', 10, 5);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" DOUBLE(10,5) NOT NULL', $sql);
    }
}
