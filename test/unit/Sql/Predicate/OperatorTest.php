<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDb\Sql\Predicate\Operator;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Operator::class, '__construct')]
#[CoversMethod(Operator::class, 'getLeft')]
#[CoversMethod(Operator::class, 'setLeft')]
#[CoversMethod(Operator::class, 'getOperator')]
#[CoversMethod(Operator::class, 'setOperator')]
#[CoversMethod(Operator::class, 'getRight')]
#[CoversMethod(Operator::class, 'setRight')]
#[CoversMethod(Operator::class, 'toSql')]
final class OperatorTest extends TestCase
{
    public function testEmptyConstructorYieldsNullLeftAndRightValues(): void
    {
        $operator = new Operator();
        self::assertNull($operator->getLeft());
        self::assertNull($operator->getRight());
    }

    public function testEmptyConstructorYieldsDefaultsForOperatorAndLeftAndRightTypes(): void
    {
        $operator = new Operator();
        self::assertEquals(Operator::OP_EQ, $operator->getOperator());
    }

    public function testCanPassAllValuesToConstructor(): void
    {
        $operator = new Operator('bar', '>=', 'foo.bar');
        self::assertEquals(Operator::OP_GTE, $operator->getOperator());

        $left = $operator->getLeft();
        self::assertInstanceOf(ArgumentInterface::class, $left);
        self::assertEquals('bar', $left->getValue());
        self::assertEquals(ArgumentType::Identifier, $left->getType());

        $right = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right);
        self::assertEquals('foo.bar', $right->getValue());
        self::assertEquals(ArgumentType::Value, $right->getType());

        $operator = new Operator(new Value('bar'), '>=', new Identifier('foo.bar'));
        self::assertEquals(Operator::OP_GTE, $operator->getOperator());

        $left = $operator->getLeft();
        self::assertInstanceOf(ArgumentInterface::class, $left);
        self::assertEquals('bar', $left->getValue());
        self::assertEquals(ArgumentType::Value, $left->getType());

        $right = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right);
        self::assertEquals('foo.bar', $right->getValue());
        self::assertEquals(ArgumentType::Identifier, $right->getType());

        $operator = new Operator('bar', '>=', 0);

        $right = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right);
        self::assertEquals(0, $right->getValue());
        self::assertEquals(ArgumentType::Value, $right->getType());
    }

    public function testLeftIsMutable(): void
    {
        $operator = new Operator();

        // First mutation
        $result = $operator->setLeft('foo.bar');

        // Verify fluent interface
        self::assertSame($operator, $result);

        // Verify the first mutation occurred
        $left1 = $operator->getLeft();
        self::assertInstanceOf(ArgumentInterface::class, $left1);
        self::assertEquals('foo.bar', $left1->getValue());
        self::assertEquals(ArgumentType::Identifier, $left1->getType());

        // Second mutation with different data to verify mutability
        $operator->setLeft('baz.qux');

        // Verify the instance was actually mutated
        $left2 = $operator->getLeft();
        self::assertInstanceOf(ArgumentInterface::class, $left2);
        self::assertEquals('baz.qux', $left2->getValue());
        self::assertEquals(ArgumentType::Identifier, $left2->getType());
    }

    public function testRightIsMutable(): void
    {
        $operator = new Operator();

        // First mutation - default type (Value)
        $result = $operator->setRight('bar');

        // Verify fluent interface
        self::assertSame($operator, $result);

        // Verify the first mutation occurred
        $right1 = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right1);
        self::assertEquals('bar', $right1->getValue());
        self::assertEquals(ArgumentType::Value, $right1->getType());

        // Second mutation - with explicit type (Identifier) using factory
        $operator->setRight(new Identifier('bar'));

        // Verify the instance was actually mutated (same value, different type)
        $right2 = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right2);
        self::assertEquals('bar', $right2->getValue());
        self::assertEquals(ArgumentType::Identifier, $right2->getType());

        // Third mutation - different value with default type
        $operator->setRight('qux');

        // Verify the instance was mutated again
        $right3 = $operator->getRight();
        self::assertInstanceOf(ArgumentInterface::class, $right3);
        self::assertEquals('qux', $right3->getValue());
        self::assertEquals(ArgumentType::Value, $right3->getType());
    }

    public function testOperatorIsMutable(): void
    {
        $operator = new Operator();
        $operator->setOperator(Operator::OP_LTE);
        self::assertEquals(Operator::OP_LTE, $operator->getOperator());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfLeftAndRightAndArrayOfTypes(): void
    {
        $operator = new Operator();
        $operator
            ->setLeft(new Value('foo'))
            ->setOperator('>=')
            ->setRight(new Identifier('foo.bar'));

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $operator->toSql($renderer, '', $paramIndex);

        self::assertEquals('\'foo\' >= "foo"."bar"', $sql);
    }

    public function testGetExpressionDataThrowsExceptionWhenLeftNotSet(): void
    {
        $operator = new Operator();
        $operator->setRight('value');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Left expression must be specified');
        $operator->toSql($renderer, '', $paramIndex);
    }

    public function testGetExpressionDataThrowsExceptionWhenRightNotSet(): void
    {
        $operator = new Operator();
        $operator->setLeft('left');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Right expression must be specified');
        $operator->toSql($renderer, '', $paramIndex);
    }
}
