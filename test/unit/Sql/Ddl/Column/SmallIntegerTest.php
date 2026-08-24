<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SmallInteger::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class SmallIntegerTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column         = new SmallInteger('foo');
        $expressionData = $column->getExpressionData();

        static::assertSame(
            '%s %s NOT NULL',
            $expressionData['spec'],
        );

        static::assertEquals(
            [
                Argument::Identifier('foo'),
                Argument::Literal('SMALLINT'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function objectConstruction(): void
    {
        $integer = new SmallInteger('foo');
        static::assertSame('foo', $integer->getName());
    }
}
