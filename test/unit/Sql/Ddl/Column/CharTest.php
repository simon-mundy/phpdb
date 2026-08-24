<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\Char;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Char::class, 'getExpressionData')]
final class CharTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Char('foo', 20);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('CHAR'),
                new Literal('20'),
            ],
            $expressionData['values'],
        );
    }
}
