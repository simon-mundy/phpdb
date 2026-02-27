<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * This is a unit testing test case.
 * A unit here is a method, there will be at least one test per method
 *
 * Expression is a value object with no dependencies/collaborators, therefore, no fixure needed
 */
#[CoversMethod(Expression::class, '__construct')]
#[CoversMethod(Expression::class, 'setExpression')]
#[CoversMethod(Expression::class, 'getExpression')]
#[CoversMethod(Expression::class, 'setParameters')]
#[CoversMethod(Expression::class, 'getParameters')]
#[CoversMethod(Expression::class, 'renderSql')]
final class ExpressionTest extends TestCase
{
    private function renderSql(Expression $expression): string
    {
        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;

        return $expression->renderSql($processor, '', $paramIndex);
    }

    public function testSetExpression(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setExpression('Foo Bar');

        // Verify fluent interface
        self::assertSame($expression, $result);

        // Verify the first mutation occurred
        self::assertEquals('Foo Bar', $expression->getExpression());

        // Second mutation to verify mutability
        $expression->setExpression('Baz Qux');

        // Verify the instance was actually mutated
        self::assertEquals('Baz Qux', $expression->getExpression());
    }

    public function testSetExpressionException(): void
    {
        $expression = new Expression();
        $this->expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $expression->setExpression(null);

        $expression = new Expression();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplied expression must not be an empty string.');
        $expression->setExpression('');
    }

    public function testSetParameters(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setParameters('foo');

        // Verify fluent interface
        self::assertSame($expression, $result);

        // Verify the first mutation occurred
        self::assertEquals([new Value('foo')], $expression->getParameters());

        // Second mutation to verify mutability (setParameters appends)
        $expression->setParameters('bar');

        // Verify the instance was actually mutated (now has both parameters)
        self::assertEquals([new Value('foo'), new Value('bar')], $expression->getParameters());
    }

    public function testGetExpressionData(): void
    {
        $expression = new Expression(
            'X SAME AS ? AND Y = ? BUT LITERALLY ?',
            [
                new Argument\Identifier('foo'),
                new Argument\Value(5),
                new Argument\Literal('FUNC(FF%X)'),
            ]
        );

        $sql = $this->renderSql($expression);

        self::assertEquals('X SAME AS "foo" AND Y = \'5\' BUT LITERALLY FUNC(FF%X)', $sql);
    }

    public function testGetExpressionDataWillEscapePercent(): void
    {
        $expression = new Expression('X LIKE "foo%"');

        $sql = $this->renderSql($expression);

        self::assertEquals('X LIKE "foo%"', $sql);
    }

    public function testConstructorWithLiteralZero(): void
    {
        $expression = new Expression('0');
        self::assertSame('0', $expression->getExpression());
    }

    #[Group('7407')]
    public function testGetExpressionPreservesPercentageSignInFromUnixtime(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);

        self::assertSame($expressionString, $expression->getExpression());
    }

    public function testNamedParameterSyntaxPreservedAsLiteral(): void
    {
        $expression = new Expression('uf.user_id = :user_id OR uf.friend_id = :user_id');

        $sql = $this->renderSql($expression);

        self::assertEquals('uf.user_id = :user_id OR uf.friend_id = :user_id', $sql);
    }

    #[DataProvider('falsyExpressionParametersProvider')]
    public function testConstructorWithFalsyValidParameters(mixed $falsyParameter): void
    {
        $expression = new Expression('?', $falsyParameter);

        // Verify the parameter was stored correctly
        $params = $expression->getParameters();
        self::assertCount(1, $params);
        self::assertEquals($falsyParameter, $params[0]->getValue());
    }

    public function testConstructorWithInvalidParameter(): void
    {
        $this->expectException(TypeError::class);
        new Expression('?', (object) []);
    }

    /** @psalm-return array<array-key, array{0: mixed}> */
    public static function falsyExpressionParametersProvider(): array
    {
        return [
            [''],
            ['0'],
            [0],
            [0.0],
            [false],
        ];
    }

    public function testExpressionWithoutPlaceholdersPreservesColonNames(): void
    {
        $expression = new Expression(':a + :b');

        $sql = $this->renderSql($expression);

        self::assertEquals(':a + :b', $sql);
    }

    public function testGetExpressionDataThrowsExceptionWhenParameterCountMismatch(): void
    {
        $expression = new Expression('? AND ?', [1]); // Two placeholders but only one parameter

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The number of replacements in the expression does not match the number of parameters'
        );
        $this->renderSql($expression);
    }

    public function testConstructorWithMultipleArguments(): void
    {
        // Test deprecated multi-argument constructor
        $expression = new Expression('? + ? - ?', 1, 2, 3);

        $sql = $this->renderSql($expression);

        self::assertEquals("'1' + '2' - '3'", $sql);
    }
}
