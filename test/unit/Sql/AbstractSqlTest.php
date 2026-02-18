<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function current;
use function key;
use function next;
use function preg_match;
use function uniqid;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(SqlProcessor::class, 'renderExpression')]
#[CoversMethod(SqlProcessor::class, 'resolveTable')]
final class AbstractSqlTest extends TestCase
{
    protected DriverInterface&MockObject $mockDriver;

    protected function setUp(): void
    {
        $this->mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $this->mockDriver
            ->expects($this->any())
            ->method('getPrepareType')
            ->willReturn(DriverInterface::PARAMETERIZATION_NAMED);
        $this->mockDriver
            ->expects($this->any())
            ->method('formatParameterName')
            ->willReturnCallback(fn($x): string => ':' . $x);
    }

    public function testProcessExpressionWithoutParameterContainer(): void
    {
        $expression   = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams = $this->invokeProcessExpression($expression);

        self::assertEquals("\"x\" > '5' AND y < '10'", $sqlAndParams);
    }

    public function testProcessExpressionWithParameterContainerAndParameterizationTypeNamed(): void
    {
        $parameterContainer = new ParameterContainer();
        $expression         = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams       = $this->invokeProcessExpression($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        // Verify SQL uses named parameters
        self::assertMatchesRegularExpression('#"x" > :expr\d+Param1 AND y < :expr\d+Param2#', $sqlAndParams);

        // Verify parameter names and values
        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumber = $matches[1];

        self::assertMatchesRegularExpression('#expr\d+Param1#', key($parameters));
        self::assertEquals(5, current($parameters));
        next($parameters);
        self::assertMatchesRegularExpression('#expr\d+Param2#', key($parameters));
        self::assertEquals(10, current($parameters));

        // Verify next invocation increments expression number
        $parameterContainer = new ParameterContainer();
        $this->invokeProcessExpression($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumberNext = $matches[1];

        self::assertEquals(1, (int) $expressionNumberNext - (int) $expressionNumber);
    }

    public function testProcessExpressionWorksWithExpressionContainingStringParts(): void
    {
        $expression = new Predicate\Expression('x = ?', 5);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpression($predicateSet);

        self::assertEquals("(x = '5')", $sqlAndParams);
    }

    public function testProcessExpressionWorksWithExpressionContainingSelectObject(): void
    {
        $select = new Select();
        $select->from('x')->where->like('bar', 'Foo%');
        $expression = new Predicate\In('x', $select);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpression($predicateSet);

        self::assertEquals('("x" IN (SELECT "x".* FROM "x" WHERE "bar" LIKE \'Foo%\'))', $sqlAndParams);
    }

    public function testProcessExpressionWorksWithExpressionContainingExpressionObject(): void
    {
        $expression = new Predicate\Operator(
            'release_date',
            '=',
            new Expression('FROM_UNIXTIME(?)', 100000000)
        );

        $sqlAndParams = $this->invokeProcessExpression($expression);
        self::assertEquals('"release_date" = FROM_UNIXTIME(\'100000000\')', $sqlAndParams);
    }

    #[Group('7407')]
    public function testProcessExpressionWorksWithExpressionObjectWithPercentageSigns(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);
        $sqlString        = $this->invokeProcessExpression($expression);

        self::assertSame($expressionString, $sqlString);
    }

    public function testProcessExpressionWorksWithNamedParameterPrefix(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = uniqid();
        $expression           = new Expression('FROM_UNIXTIME(?)', [10000000]);
        $this->invokeProcessExpression($expression, $parameterContainer, $namedParameterPrefix);

        self::assertSame($namedParameterPrefix . '1', (string) key($parameterContainer->getNamedArray()));
    }

    public function testProcessExpressionWorksWithNamedParameterPrefixContainingWhitespace(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = "string\ncontaining white space";
        $expression           = new Expression('FROM_UNIXTIME(?)', [10000000]);
        $this->invokeProcessExpression($expression, $parameterContainer, $namedParameterPrefix);

        self::assertSame('string__containing__white__space1', key($parameterContainer->getNamedArray()));
    }

    public function testResolveTableWithTableIdentifierAndSchema(): void
    {
        $table     = new TableIdentifier('users', 'public');
        $processor = new SqlProcessor(new TrustingSql92Platform(), $this->mockDriver);

        $result = $processor->resolveTable($table);

        self::assertStringContainsString('public', $result);
        self::assertStringContainsString('users', $result);
    }

    public function testResolveTableWithSelect(): void
    {
        $select    = new Select('foo');
        $processor = new SqlProcessor(new TrustingSql92Platform(), $this->mockDriver);

        $result = $processor->resolveTable($select);

        self::assertStringStartsWith('(', $result);
        self::assertStringEndsWith(')', $result);
        self::assertStringContainsString('SELECT', $result);
    }

    protected function invokeProcessExpression(
        ExpressionInterface $expression,
        ParameterContainer|null $parameterContainer = null,
        string|null $namedParameterPrefix = null
    ): string {
        $processor = new SqlProcessor(
            new TrustingSql92Platform(),
            $this->mockDriver,
            $parameterContainer,
        );

        return $processor->renderExpression($expression, $namedParameterPrefix);
    }
}
