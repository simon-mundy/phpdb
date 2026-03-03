<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SmallInteger::class, '__construct')]
#[CoversMethod(Column::class, 'toSql')]
final class SmallIntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new SmallInteger('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $column = new SmallInteger('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" SMALLINT NOT NULL', $sql);
    }
}
