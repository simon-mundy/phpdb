<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'renderSql')]
final class AbstractPrecisionColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSetDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(10, $column->getDigits());
        self::assertSame($column, $column->setDigits(12));
        self::assertEquals(12, $column->getDigits());
    }

    /**
     * @throws Exception
     */
    public function testGetDigits(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(10, $column->getDigits());
    }

    /**
     * @throws Exception
     */
    public function testSetDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(5, $column->getDecimal());
        self::assertSame($column, $column->setDecimal(2));
        self::assertEquals(2, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    public function testGetDecimal(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(5, $column->getDecimal());
    }

    /**
     * @throws Exception
     */
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractPrecisionColumn::class)
            ->setConstructorArgs(['foo', 10, 5])
            ->onlyMethods([])
            ->getMock();

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" INTEGER(10,5) NOT NULL', $sql);
    }
}
