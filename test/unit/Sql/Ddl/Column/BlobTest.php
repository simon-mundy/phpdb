<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Blob;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Blob::class, 'toSql')]
final class BlobTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Blob('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $sql = $column->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" BLOB NOT NULL', $sql);
    }
}
