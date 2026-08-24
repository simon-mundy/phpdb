<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Constraint\Check;
use PhpDb\Sql\ExpressionInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, '__construct')]
#[CoversMethod(Check::class, 'getExpressionData')]
final class CheckTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $expressionData = $check->getExpressionData();

        static::assertSame('CONSTRAINT %s CHECK (%s)', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('id>0'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataComposesAnExpressionInterface(): void
    {
        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->once())
            ->method('getExpressionData')
            ->willReturn([
                'spec'   => '%s > %s',
                'values' => [Argument::identifier('id'), Argument::value(0)],
            ]);

        $check = new Check($expression, 'foo');

        $expressionData = $check->getExpressionData();

        static::assertSame('CONSTRAINT %s CHECK (%s > %s)', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::identifier('id'),
                Argument::value(0),
            ],
            $expressionData['values'],
        );
    }
}
