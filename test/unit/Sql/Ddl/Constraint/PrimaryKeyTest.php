<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'toSql')]
final class PrimaryKeyTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $pk = new PrimaryKey('foo');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $pk->toSql($processor, '', $paramIndex);

        self::assertEquals('PRIMARY KEY ("foo")', $sql);
    }
}
