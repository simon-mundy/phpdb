<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getExpressionData')]
final class AbstractPrecisionColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(5, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(10, $column->getDigits());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function setDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(5, $column->getDecimal());
        static::assertSame($column, $column->setDecimal(2));
        static::assertSame(2, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function setDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(10, $column->getDigits());
        static::assertSame($column, $column->setDigits(12));
        static::assertSame(12, $column->getDigits());
    }
}
