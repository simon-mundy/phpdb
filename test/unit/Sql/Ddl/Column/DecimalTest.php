<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Decimal;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, '__construct')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
final class DecimalTest extends TestCase
{
    #[Test]
    public function constructorSetsDigitsAndDecimal(): void
    {
        $column = new Decimal('price', 10, 2);

        static::assertSame(10, $column->getDigits());
        static::assertSame(2, $column->getDecimal());
    }

    #[Test]
    public function getExpressionData(): void
    {
        $column = new Decimal('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DECIMAL'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithNullDecimal(): void
    {
        $column = new Decimal('amount', 10);
        $column->setDecimal(null);

        $expressionData = $column->getExpressionData();

        // Without decimal, length expression should be just the digits (as string)
        $values = $expressionData['values'];
        static::assertCount(3, $values);
        static::assertEquals(Argument::identifier('amount'), $values[0]);
        static::assertEquals(Argument::literal('DECIMAL'), $values[1]);
        // The third value should be "10" (string representation)
        static::assertEquals(Argument::literal((string) 10), $values[2]);
    }

    #[Test]
    public function inheritanceFromAbstractPrecisionColumn(): void
    {
        $column = new Decimal('test');
        static::assertInstanceOf(AbstractPrecisionColumn::class, $column);
    }

    #[Test]
    public function setDecimalAndGetDecimal(): void
    {
        $column = new Decimal('value');
        $result = $column->setDecimal(4);

        static::assertSame($column, $result); // Fluent interface
        static::assertSame(4, $column->getDecimal());
    }

    #[Test]
    public function setDigitsAndGetDigits(): void
    {
        $column = new Decimal('amount');
        $result = $column->setDigits(15);

        static::assertSame($column, $result); // Fluent interface
        static::assertSame(15, $column->getDigits());
    }
}
