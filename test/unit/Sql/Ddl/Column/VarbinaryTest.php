<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Varbinary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varbinary::class, 'getExpressionData')]
final class VarbinaryTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Varbinary('foo', 20);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARBINARY'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );
    }
}
