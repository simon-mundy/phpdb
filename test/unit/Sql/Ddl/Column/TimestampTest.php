<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\AbstractTimestampColumn;
use PhpDb\Sql\Ddl\Column\Timestamp;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Timestamp::class, 'toSql')]
#[CoversMethod(AbstractTimestampColumn::class, 'toSql')]
final class TimestampTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Timestamp('foo');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"foo" TIMESTAMP NOT NULL', $sql);
    }

    public function testGetExpressionDataWithOnUpdateOption(): void
    {
        $column = new Timestamp('created_at');
        $column->setOption('on_update', true);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"created_at" TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP', $sql);
    }

    public function testGetExpressionDataWithoutOnUpdateOption(): void
    {
        $column = new Timestamp('updated_at');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($processor, '', $paramIndex);

        // Should NOT include ON UPDATE
        self::assertEquals('"updated_at" TIMESTAMP NOT NULL', $sql);
    }

    public function testGetExpressionDataWithCurrentTimestampDefault(): void
    {
        $column = new Timestamp('created_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->toSql($processor, '', $paramIndex);

        self::assertEquals('"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    public function testGetExpressionDataWithCurrentTimestampDefaultAndOnUpdate(): void
    {
        $column = new Timestamp('updated_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));
        $column->setOption('on_update', true);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->toSql($processor, '', $paramIndex);

        $expected = '"updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        self::assertEquals($expected, $sql);
    }

    public function testInheritanceFromAbstractTimestampColumn(): void
    {
        $column = new Timestamp('test');
        self::assertInstanceOf(AbstractTimestampColumn::class, $column);
    }
}
