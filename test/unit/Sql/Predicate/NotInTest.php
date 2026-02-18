<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Predicate\NotIn;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\TestCase;

final class NotInTest extends TestCase
{
    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new NotIn();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo"."bar" NOT IN (\'1\', \'2\', \'3\')', $sql);
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select = new Select('foo');
        $in     = new NotIn('foo', $select);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->renderSql($processor, '', $paramIndex);

        self::assertStringStartsWith('"foo" NOT IN (SELECT "foo"', $sql);
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select('foo');
        $in     = new NotIn('foo', $select);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->renderSql($processor, '', $paramIndex);

        self::assertStringStartsWith('"foo" NOT IN (SELECT "foo"', $sql);
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select('foo');
        $in     = new NotIn(Argument::identifiers(['foo', 'bar']), $select);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $in->renderSql($processor, '', $paramIndex);

        self::assertStringStartsWith('"foo", "bar" NOT IN (SELECT "foo"', $sql);
    }
}
