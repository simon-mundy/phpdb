<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Index;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Ddl\Index\Index;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Index::class, '__construct')]
#[CoversMethod(Index::class, 'setType')]
#[CoversMethod(Index::class, 'getType')]
#[CoversMethod(Index::class, 'getExpressionData')]
final class IndexTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new Index('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();

        self::assertEquals('INDEX %s(%s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataWithLength(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10, 5]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s(5))', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
            new Identifier('bar'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataWithLengthUnmatched(): void
    {
        $key = new Index(['foo', 'bar'], 'my_uk', [10]);

        $expressionData = $key->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_uk'),
            new Identifier('foo'),
            new Identifier('bar'),
        ], $expressionData['values']);
    }

    public function testSetTypeAndGetType(): void
    {
        $index = new Index('foo', 'my_idx');
        self::assertNull($index->getType());

        $result = $index->setType('BTREE');
        self::assertSame($index, $result);
        self::assertEquals('BTREE', $index->getType());
    }

    public function testGetExpressionDataWithTypeDoesNotRenderUsing(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('BTREE');

        $expressionData = $index->getExpressionData();

        // Type is stored but not rendered in base class (adapter-specific)
        self::assertEquals('INDEX %s(%s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_idx'),
            new Identifier('foo'),
        ], $expressionData['values']);
        self::assertEquals('BTREE', $index->getType());
    }

    public function testGetExpressionDataWithHashTypeDoesNotRenderUsing(): void
    {
        $index = new Index('foo', 'my_idx');
        $index->setType('HASH');

        $expressionData = $index->getExpressionData();

        self::assertEquals('INDEX %s(%s)', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_idx'),
            new Identifier('foo'),
        ], $expressionData['values']);
        self::assertEquals('HASH', $index->getType());
    }

    public function testGetExpressionDataWithTypeAndLengths(): void
    {
        $index = new Index(['foo', 'bar'], 'my_idx', [10, 5]);
        $index->setType('BTREE');

        $expressionData = $index->getExpressionData();

        self::assertEquals('INDEX %s(%s(10), %s(5))', $expressionData['spec']);
        self::assertEquals([
            new Identifier('my_idx'),
            new Identifier('foo'),
            new Identifier('bar'),
        ], $expressionData['values']);
        self::assertEquals('BTREE', $index->getType());
    }
}
