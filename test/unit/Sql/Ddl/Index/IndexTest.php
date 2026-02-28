<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Index;

use PhpDb\Sql\Ddl\Index\Index;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, '__construct')]
#[CoversMethod(Index::class, 'renderSql')]
#[CoversMethod(Index::class, 'setType')]
#[CoversMethod(Index::class, 'getType')]
final class IndexTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $uk->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_uk"("foo")', $sql);
    }

    public function testGetExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $key->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_uk"("foo"(10), "bar"(5))', $sql);
    }

    public function testGetExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $key->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_uk"("foo"(10), "bar")', $sql);
    }

    public function testSetTypeAndGetType(): void
    {
        $index = new Index('foo', 'my_idx');
        self::assertNull($index->getType());

        $result = $index->setType('BTREE');
        self::assertSame($index, $result);
        self::assertEquals('BTREE', $index->getType());
    }

    public function testGetExpressionDataWithBtreeType(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('BTREE');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $index->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_idx"("foo") USING BTREE', $sql);
    }

    public function testGetExpressionDataWithHashType(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('HASH');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $index->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_idx"("foo") USING HASH', $sql);
    }

    public function testGetExpressionDataWithTypeAndLengths(): void
    {
        $index = new Index(['foo', 'bar'], 'my_idx', [10, 5]);
        $index->setType('BTREE');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $index->renderSql($processor, '', $paramIndex);

        self::assertEquals('INDEX "my_idx"("foo"(10), "bar"(5)) USING BTREE', $sql);
    }
}
