<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\Check;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, '__construct')]
#[CoversMethod(Check::class, 'renderSql')]
final class CheckTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $processor = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql = $check->renderSql($processor, '', $paramIndex);

        self::assertEquals('CONSTRAINT "foo" CHECK (id>0)', $sql);
    }
}
