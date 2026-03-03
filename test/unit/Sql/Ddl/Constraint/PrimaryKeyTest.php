<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'toSql')]
final class PrimaryKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $pk = new PrimaryKey('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $pk->toSql($renderer, '', $paramIndex);

        self::assertEquals('PRIMARY KEY ("foo")', $sql);
    }
}
