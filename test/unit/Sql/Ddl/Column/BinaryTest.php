<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Binary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Binary::class, 'getExpressionData')]
final class BinaryTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Binary('foo', 10_000_000);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('BINARY'),
                Argument::literal('10000000'),
            ],
            $expressionData['values'],
        );
    }
}
