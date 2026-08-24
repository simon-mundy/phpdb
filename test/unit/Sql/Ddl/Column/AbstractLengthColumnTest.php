<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
final class AbstractLengthColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 4])
            ->onlyMethods([])
            ->getMock();

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('INTEGER'),
                new Literal('4'),
            ],
            $expressionData['values'],
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(55, $column->getLength());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function setLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)
            ->setConstructorArgs(['foo', 55])
            ->onlyMethods([])
            ->getMock();
        static::assertSame(55, $column->getLength());
        static::assertSame($column, $column->setLength(20));
        static::assertSame(20, $column->getLength());
    }
}
