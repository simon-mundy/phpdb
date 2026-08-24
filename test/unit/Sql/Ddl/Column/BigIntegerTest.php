<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\BigInteger;
use PhpDb\Sql\Ddl\Column\Column;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(BigInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class BigIntegerTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column         = new BigInteger('foo');
        $expressionData = $column->getExpressionData();

        static::assertSame(
            '%s %s NOT NULL',
            $expressionData['spec'],
        );

        static::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('BIGINT'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function objectConstruction(): void
    {
        $integer = new BigInteger('foo');
        static::assertSame('foo', $integer->getName());
    }
}
