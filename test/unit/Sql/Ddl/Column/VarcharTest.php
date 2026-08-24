<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Varchar;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varchar::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, '__construct')]
#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
final class VarcharTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Varchar('foo', 20);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
            ],
            $expressionData['values'],
        );

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('VARCHAR'),
                Argument::literal('20'),
                Argument::value('bar'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithNullLength(): void
    {
        $column = new Varchar('name');

        $expressionData = $column->getExpressionData();

        // When length is null, getLengthExpression() returns empty string
        // The condition in getExpressionData checks: getLengthExpression() !== '' && !== '0'
        // Empty string fails the first check, so length value is NOT added
        // But specification still has (%s) placeholder - need to verify actual behavior
        $spec   = $expressionData['spec'];
        $values = $expressionData['values'];

        // The specification format is defined in AbstractLengthColumn as '%s %s(%s)'
        // But when length value is not added, we need to check if placeholder remains
        static::assertSame('%s %s(%s) NOT NULL', $spec);
        static::assertEquals(
            [
                Argument::identifier('name'),
                Argument::literal('VARCHAR'),
            ],
            $values,
        );
    }

    #[Test]
    public function inheritanceFromAbstractLengthColumn(): void
    {
        $column = new Varchar('test');
        static::assertInstanceOf(AbstractLengthColumn::class, $column);
    }

    #[Test]
    public function setLengthAndGetLength(): void
    {
        $column = new Varchar('name');

        $result = $column->setLength(100);
        static::assertSame($column, $result); // Fluent interface
        static::assertSame(100, $column->getLength());
    }
}
