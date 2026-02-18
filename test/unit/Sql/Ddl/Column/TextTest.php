<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Text;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'renderSql')]
final class TextTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Text('foo');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo" TEXT NOT NULL', $sql);
    }
}
