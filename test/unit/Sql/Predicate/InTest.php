<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(In::class, '__construct')]
#[CoversMethod(In::class, 'setIdentifier')]
#[CoversMethod(In::class, 'getIdentifier')]
#[CoversMethod(In::class, 'setValueSet')]
#[CoversMethod(In::class, 'getValueSet')]
#[CoversMethod(In::class, 'toSql')]
final class InTest extends TestCase
{
    public function testEmptyConstructorYieldsNullIdentifierAndValueSet(): void
    {
        $in = new In();
        self::assertNull($in->getIdentifier());
        self::assertNull($in->getValueSet());
    }

    public function testCanPassIdentifierAndValueSetToConstructor(): void
    {
        $in = new In('foo.bar', [1, 2]);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify value set was set correctly
        $valueSet = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet);
        self::assertEquals([1, 2], $valueSet->getValue());
        self::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    public function testCanPassIdentifierAndEmptyValueSetToConstructor(): void
    {
        $in = new In('foo.bar', []);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify empty value set was set correctly
        $valueSet = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet);
        self::assertEquals([], $valueSet->getValue());
        self::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    public function testIdentifierIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setIdentifier('foo.bar');

        // Verify fluent interface
        self::assertSame($in, $result);

        // Verify the first mutation occurred
        $identifier1 = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('foo.bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $in->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $in->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz.qux', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    public function testValueSetIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setValueSet([1, 2]);

        // Verify fluent interface
        self::assertSame($in, $result);

        // Verify the first mutation occurred
        $valueSet1 = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet1);
        self::assertEquals([1, 2], $valueSet1->getValue());
        self::assertEquals(ArgumentType::Values, $valueSet1->getType());

        // Second mutation with different data to verify mutability
        $in->setValueSet([3, 4, 5]);

        // Verify the instance was actually mutated
        $valueSet2 = $in->getValueSet();
        self::assertInstanceOf(ArgumentInterface::class, $valueSet2);
        self::assertEquals([3, 4, 5], $valueSet2->getValue());
        self::assertEquals(ArgumentType::Values, $valueSet2->getType());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo"."bar" IN (\'1\', \'2\', \'3\')', $sql);
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select = new Select('foo');
        $in     = new In(Argument::value('foo'), $select);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->toSql($renderer, '', $paramIndex);

        self::assertStringStartsWith('\'foo\' IN (SELECT "foo"', $sql);
    }

    public function testGetExpressionDataWithEmptyValues(): void
    {
        $in = new In('foo', []);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->toSql($renderer, '', $paramIndex);

        self::assertEquals('"foo" IN ()', $sql);
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select('foo');
        $in     = new In(Argument::identifier('foo'), $select);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->toSql($renderer, '', $paramIndex);

        self::assertStringStartsWith('"foo" IN (SELECT "foo"', $sql);
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select('foo');
        $in     = new In(Argument::identifiers(['foo', 'bar']), $select);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->toSql($renderer, '', $paramIndex);

        self::assertStringStartsWith('"foo", "bar" IN (SELECT "foo"', $sql);
    }

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $in = new In();
        $in->setValueSet([1, 2]);

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $in->toSql($renderer, '', $paramIndex);
    }

    public function testGetExpressionDataThrowsExceptionWhenValueSetNotSet(): void
    {
        $in = new In();
        $in->setIdentifier('foo');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value set must be provided for IN predicate');
        $in->toSql($renderer, '', $paramIndex);
    }
}
