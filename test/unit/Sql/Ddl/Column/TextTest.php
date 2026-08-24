<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Text;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'getExpressionData')]
final class TextTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Text('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('TEXT'),
            ],
            $expressionData['values'],
        );
    }
}
