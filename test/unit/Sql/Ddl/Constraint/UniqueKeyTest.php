<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(UniqueKey::class, 'toSql')]
final class UniqueKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $uk = new UniqueKey('foo', 'my_uk');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $uk->toSql($processor, '', $paramIndex);

        self::assertEquals('CONSTRAINT "my_uk" UNIQUE ("foo")', $sql);
    }
}
