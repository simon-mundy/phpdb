<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\BigInteger;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(BigInteger::class, '__construct')]
#[CoversMethod(Column::class, 'toSql')]
final class BigIntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new BigInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $column = new BigInteger('foo');
        $sql    = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"foo" BIGINT NOT NULL', $sql);
    }
}
