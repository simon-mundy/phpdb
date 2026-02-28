<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'toSql')]
final class AbstractLengthColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(55, $column->getLength());
        self::assertSame($column, $column->setLength(20));
        self::assertEquals(20, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        self::assertEquals(55, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 4])
            ->onlyMethods([])
            ->getMock();

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"foo" INTEGER(4) NOT NULL', $sql);
    }
}
