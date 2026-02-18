<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Decimal;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'renderSql')]
#[CoversMethod(AbstractPrecisionColumn::class, '__construct')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
final class DecimalTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Decimal('foo', 10, 5);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" DECIMAL(10,5) NOT NULL', $sql);
    }

    public function testConstructorSetsDigitsAndDecimal(): void
    {
        $column = new Decimal('price', 10, 2);

        self::assertEquals(10, $column->getDigits());
        self::assertEquals(2, $column->getDecimal());
    }

    public function testSetDigitsAndGetDigits(): void
    {
        $column = new Decimal('amount');
        $result = $column->setDigits(15);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(15, $column->getDigits());
    }

    public function testSetDecimalAndGetDecimal(): void
    {
        $column = new Decimal('value');
        $result = $column->setDecimal(4);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(4, $column->getDecimal());
    }

    public function testGetExpressionDataWithNullDecimal(): void
    {
        $column = new Decimal('amount', 10);
        $column->setDecimal(null);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->renderSql($processor, '', $paramIndex);

        // Without decimal, length expression should be just the digits
        self::assertEquals('"amount" DECIMAL(10) NOT NULL', $sql);
    }

    public function testInheritanceFromAbstractPrecisionColumn(): void
    {
        $column = new Decimal('test');
        self::assertInstanceOf(AbstractPrecisionColumn::class, $column);
    }
}
