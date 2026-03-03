<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varchar::class, 'toSql')]
#[CoversMethod(AbstractLengthColumn::class, '__construct')]
#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'toSql')]
final class VarcharTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $column = new Varchar('foo', 20);

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" VARCHAR(20) NOT NULL', $sql);

        $column->setDefault('bar');

        $paramIndex = 1;
        $sql        = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" VARCHAR(20) NOT NULL DEFAULT \'bar\'', $sql);
    }

    public function testSetLengthAndGetLength(): void
    {
        $column = new Varchar('name');

        $result = $column->setLength(100);
        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(100, $column->getLength());
    }

    public function testGetExpressionDataWithNullLength(): void
    {
        $column = new Varchar('name');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        // When length is null, getLengthExpression() returns empty string
        // which means no length modifier is appended
        self::assertEquals('"name" VARCHAR NOT NULL', $sql);
    }

    public function testInheritanceFromAbstractLengthColumn(): void
    {
        $column = new Varchar('test');
        self::assertInstanceOf(AbstractLengthColumn::class, $column);
    }
}
