<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Text;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'toSql')]
final class TextTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Text('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" TEXT NOT NULL', $sql);
    }
}
