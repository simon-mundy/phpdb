<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use ErrorException;
use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDb\Sql\Predicate\Predicate;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class PredicateTest extends TestCase
{
    public function testEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->equalTo('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" = \'bar\'', $sql);
    }

    public function testNotEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notEqualTo('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" != \'bar\'', $sql);
    }

    public function testLessThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThan('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" < \'bar\'', $sql);
    }

    public function testGreaterThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThan('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" > \'bar\'', $sql);
    }

    public function testLessThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThanOrEqualTo('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" <= \'bar\'', $sql);
    }

    public function testGreaterThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThanOrEqualTo('foo.bar', 'bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" >= \'bar\'', $sql);
    }

    public function testLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->like('foo.bar', 'bar%');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" LIKE \'bar%\'', $sql);
    }

    public function testNotLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notLike('foo.bar', 'bar%');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" NOT LIKE \'bar%\'', $sql);
    }

    public function testLiteralCreatesLiteralPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->literal('foo.bar = ?');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('foo.bar = ?', $sql);
    }

    public function testIsNullCreatesIsNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IS NULL', $sql);
    }

    public function testIsNotNullCreatesIsNotNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNotNull('foo.bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IS NOT NULL', $sql);
    }

    public function testInCreatesInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->in('foo.bar', ['foo', 'bar']);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IN (\'foo\', \'bar\')', $sql);
    }

    public function testNotInCreatesNotInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notIn('foo.bar', ['foo', 'bar']);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" NOT IN (\'foo\', \'bar\')', $sql);
    }

    public function testBetweenCreatesBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->between('foo.bar', 1, 10);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" BETWEEN \'1\' AND \'10\'', $sql);
    }

    public function testBetweenCreatesNotBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notBetween('foo.bar', 1, 10);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" NOT BETWEEN \'1\' AND \'10\'', $sql);
    }

    public function testCanChainPredicateFactoriesBetweenOperators(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
            ->or
            ->isNotNull('bar.baz')
            ->and
            ->equalTo('baz.bat', 'foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IS NULL OR "bar"."baz" IS NOT NULL AND "baz"."bat" = \'foo\'', $sql);
    }

    public function testCanNestPredicates(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
                  ->nest()
                  ->isNotNull('bar.baz')
            ->and
            ->equalTo('baz.bat', 'foo')
            ->unnest();

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IS NULL AND ("bar"."baz" IS NOT NULL AND "baz"."bat" = \'foo\')', $sql);
    }

    #[TestDox('Unit test: Test expression() is chainable and returns proper values')]
    public function testExpression(): void
    {
        $predicate = new Predicate();

        // is chainable
        self::assertSame($predicate, $predicate->expression('foo = ?', 0));

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('foo = \'0\'', $sql);
    }

    #[TestDox('Unit test: Test expression() allows null $parameters')]
    public function testExpressionNullParameters(): void
    {
        $predicate = new Predicate();

        $predicate->expression('foo = bar');

        $predicates = $predicate->getPredicates();

        if (isset($predicates[0][1])) {
            $expression = $predicates[0][1];
            $this->assertInstanceOf(Expression::class, $expression);
            self::assertEquals([], $expression->getParameters());
        } else {
            $this->fail('Expression not found');
        }
    }

    #[TestDox('Unit test: Test literal() is chainable, returns proper values, and is backwards compatible with 2.0.*')]
    public function testLiteral(): void
    {
        $predicate = new Predicate();

        // is chainable
        self::assertSame($predicate, $predicate->literal('foo = bar'));

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('foo = bar', $sql);

        // test literal() is backwards-compatible, and works with with parameters
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 'bar');

        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('foo = \'bar\'', $sql);

        // test literal() is backwards-compatible, and works with with parameters, even 0 which tests as false
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 0);

        $paramIndex = 1;
        $sql        = $predicate->toSql($renderer, '', $paramIndex);

        self::assertEquals('foo = \'0\'', $sql);
    }

    /**
     * removed throws ErrorException to fix phpstan issue
     */
    public function testCanCreateExpressionsWithoutAnyBoundSqlParameters(): void
    {
        $this->markTestSkipped(
            'This test is skipped because it triggers exception in quoteValue(). That can not be expected currently.'
        );

        /** @phpstan-ignore deadCode.unreachable */
        $where1 = new Predicate();

        $where1->expression('some_expression()');

        $actual = $this->makeSqlString($where1);

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression())',
            $actual
        );
    }

    /**
     * @throws ErrorException
     */
    public function testWillBindSqlParametersToExpressionsWithGivenParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', null);

        $actual = $this->makeSqlString($where);

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'\'))',
            $actual
        );
    }

    /**
     * @throws ErrorException
     */
    public function testWillBindSqlParametersToExpressionsWithGivenStringParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', 'a string');

        $actual = $this->makeSqlString($where);

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'a string\'))',
            $actual
        );
    }

    /**
     * @throws ErrorException
     */
    private function makeSqlString(Predicate $where): string
    {
        $select = new Select('a_table');

        $select->where($where);

        // this is still faster than connecting to a real DB for this kind of test.
        // we are using unsafe SQL quoting on purpose here: this raises warnings in production.
        // ErrorHandler::start(E_USER_NOTICE);

        // try {
        //     $string = $select->getSqlString(new Sql92());
        // } finally {
        //     ErrorHandler::stop();
        // }
        $this->expectException(VunerablePlatformQuoteException::class);
        return $select->getSqlString(new Sql92());
    }
}
