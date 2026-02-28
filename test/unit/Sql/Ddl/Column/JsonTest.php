<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Json::class, 'renderSql')]
final class JsonTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Json('foo');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" JSON NOT NULL', $sql);
    }
}
