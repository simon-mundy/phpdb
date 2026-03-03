<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Json::class, 'toSql')]
final class JsonTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Json('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" JSON NOT NULL', $sql);
    }
}
