<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Boolean;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Boolean::class, 'toSql')]
#[CoversClass(Boolean::class)]
final class BooleanTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Boolean('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" BOOLEAN NOT NULL', $sql);
    }

    #[Group('6257')]
    public function testIsAlwaysNotNullable(): void
    {
        $column = new Boolean('foo', true);

        self::assertFalse($column->isNullable());

        $column->setNullable(true);

        self::assertFalse($column->isNullable());
    }
}
