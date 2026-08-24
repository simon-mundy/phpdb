<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Double;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Double::class, 'getExpressionData')]
final class DoubleTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Double('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DOUBLE'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }
}
