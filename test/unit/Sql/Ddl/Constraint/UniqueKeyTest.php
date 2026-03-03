<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(UniqueKey::class, 'toSql')]
final class UniqueKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new UniqueKey('foo', 'my_uk');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $uk->toSql($renderer, '', $paramIndex);

        self::assertEquals('CONSTRAINT "my_uk" UNIQUE ("foo")', $sql);
    }
}
