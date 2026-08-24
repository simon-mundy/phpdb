<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Integer::class, 'getExpressionData')]
#[CoversMethod(Column::class, 'getExpressionData')]
#[Group('unit')]
final class IntegerTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Integer('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );

        $column = new Integer('foo');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL PRIMARY KEY', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataExcludesLengthWhenNotSet(): void
    {
        $column = new Integer('id');

        $expressionData = $column->getExpressionData();

        static::assertStringNotContainsString('(', $expressionData['spec']);
    }

    #[Test]
    public function getExpressionDataIncludesLengthWhenOptionSet(): void
    {
        $column = new Integer('id');
        $column->setOption('length', '11');

        $expressionData = $column->getExpressionData();

        static::assertStringContainsString('(11)', $expressionData['spec']);
    }

    #[Test]
    public function objectConstruction(): void
    {
        $integer = new Integer('foo');
        static::assertSame('foo', $integer->getName());
    }
}
