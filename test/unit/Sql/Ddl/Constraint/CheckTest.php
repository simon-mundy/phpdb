<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\Check;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, '__construct')]
#[CoversMethod(Check::class, 'toSql')]
final class CheckTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $check->toSql($renderer, '', $paramIndex);

        self::assertEquals('CONSTRAINT "foo" CHECK (id>0)', $sql);
    }
}
