<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Column::class, 'toSql')]
final class IntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new Integer('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $column = new Integer('foo');
        $sql    = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" INTEGER NOT NULL', $sql);

        $column = new Integer('foo');
        $column->addConstraint(new PrimaryKey());

        $paramIndex = 1;
        $sql        = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" INTEGER NOT NULL PRIMARY KEY', $sql);
    }
}
