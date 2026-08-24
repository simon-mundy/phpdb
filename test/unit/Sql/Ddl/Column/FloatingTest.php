<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Floating;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Floating::class, 'getExpressionData')]
final class FloatingTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Floating('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('FLOAT'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }
}
