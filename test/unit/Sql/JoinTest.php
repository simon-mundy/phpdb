<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Join;
use PhpDb\Sql\Part\JoinSpec;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Select;
use PhpDbTest\DeprecatedAssertionsTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(Join::class, '__construct')]
#[CoversMethod(Join::class, 'rewind')]
#[CoversMethod(Join::class, 'current')]
#[CoversMethod(Join::class, 'key')]
#[CoversMethod(Join::class, 'next')]
#[CoversMethod(Join::class, 'valid')]
#[CoversMethod(Join::class, 'getJoins')]
#[CoversMethod(Join::class, 'add')]
#[CoversMethod(Join::class, 'count')]
#[CoversMethod(Join::class, 'reset')]
#[CoversMethod(Join::class, 'isEmpty')]
#[CoversMethod(Join::class, 'getSpecs')]
#[CoversMethod(Join::class, 'toSql')]
class JoinTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    /**
     * @throws ReflectionException
     */
    public function testInitialPositionIsZero(): void
    {
        $join = new Join();

        self::assertAttributeEquals(0, 'position', $join);
    }

    /**
     * @throws ReflectionException
     */
    public function testNextIncrementsThePosition(): void
    {
        $join = new Join();

        $join->next();

        self::assertAttributeEquals(1, 'position', $join);
    }

    /**
     * @throws ReflectionException
     */
    public function testRewindResetsPositionToZero(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        self::assertAttributeEquals(2, 'position', $join);

        $join->rewind();
        self::assertAttributeEquals(0, 'position', $join);
    }

    public function testKeyReturnsTheCurrentPosition(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        $join->next();

        self::assertEquals(3, $join->key());
    }

    public function testCurrentReturnsTheCurrentJoinSpecification(): void
    {
        $name = 'baz';
        $on   = 'foo.id = baz.id';

        $join = new Join();
        $join->add(Table::createJoinSpec($name, $on));

        $expectedSpecification = [
            'name'    => $name,
            'on'      => $on,
            'columns' => [Select::SQL_STAR],
            'type'    => Join::JOIN_INNER,
        ];

        self::assertEquals($expectedSpecification, $join->current());
    }

    public function testValidReturnsTrueIfTheIteratorIsAtAValidPositionAndFalseIfNot(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.id = baz.id'));

        self::assertTrue($join->valid());

        $join->next();

        self::assertFalse($join->valid());
    }

    #[TestDox('unit test: Test join() returns Join object (is chainable)')]
    public function testJoin(): void
    {
        $join   = new Join();
        $return = $join->add(Table::createJoinSpec('baz', 'foo.fooId = baz.fooId', [], Join::JOIN_LEFT));
        self::assertSame($join, $return);
    }

    public function testJoinFullOuter(): void
    {
        $join   = new Join();
        $return = $join->add(Table::createJoinSpec('baz', 'foo.fooId = baz.fooId', [], Join::JOIN_FULL_OUTER));
        self::assertSame($join, $return);
    }

    public function testJoinWillThrowAnExceptionIfNameIsNoValid(): void
    {
        $this->expectException(TypeError::class);
        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        Table::createJoinSpec([], false);
    }

    #[TestDox('unit test: Test count() returns correct count')]
    public function testCount(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.fooId = baz.fooId', [], Join::JOIN_LEFT));
        $join->add(Table::createJoinSpec('bar', 'foo.fooId = bar.fooId', [], Join::JOIN_LEFT));

        self::assertEquals(2, $join->count());
        self::assertCount($join->count(), $join->getJoins());
    }

    #[TestDox('unit test: Test reset() resets the joins')]
    public function testReset(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.fooId = baz.fooId', [], Join::JOIN_LEFT));
        $join->add(Table::createJoinSpec('bar', 'foo.fooId = bar.fooId', [], Join::JOIN_LEFT));
        $join->reset();

        self::assertEquals(0, $join->count());
    }

    public function testIsEmptyReturnsTrueWhenNoJoins(): void
    {
        $join = new Join();
        self::assertTrue($join->isEmpty());
    }

    public function testIsEmptyReturnsFalseAfterJoin(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.id = baz.id'));
        self::assertFalse($join->isEmpty());
    }

    public function testGetSpecsReturnsJoinSpecObjects(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.id = baz.id'));
        $join->add(Table::createJoinSpec('bar', 'foo.id = bar.id'));

        $specs = $join->getSpecs();
        self::assertCount(2, $specs);
        self::assertContainsOnlyInstancesOf(JoinSpec::class, $specs);
    }

    public function testResetClearsSpecs(): void
    {
        $join = new Join();
        $join->add(Table::createJoinSpec('baz', 'foo.id = baz.id'));
        $join->reset();

        self::assertSame([], $join->getSpecs());
        self::assertTrue($join->isEmpty());
    }
}
