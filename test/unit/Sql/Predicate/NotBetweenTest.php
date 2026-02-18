<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Predicate\NotBetween;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(NotBetween::class, 'getSpecification')]
#[CoversMethod(NotBetween::class, 'renderSql')]
final class NotBetweenTest extends TestCase
{
    protected NotBetween $notBetween;

    #[Override]
    protected function setUp(): void
    {
        $this->notBetween = new NotBetween();
    }

    public function testSpecificationIsNullByDefault(): void
    {
        self::assertNull($this->notBetween->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->notBetween
            ->setIdentifier('foo.bar')
            ->setMinValue(10)
            ->setMaxValue(19);

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $this->notBetween->renderSql($processor, '', $paramIndex);

        self::assertEquals('"foo"."bar" NOT BETWEEN \'10\' AND \'19\'', $sql);

        $this->notBetween
            ->setIdentifier(Argument::value(10))
            ->setMinValue(Argument::identifier('foo.bar'))
            ->setMaxValue(Argument::identifier('foo.baz'));

        $paramIndex = 1;
        $sql        = $this->notBetween->renderSql($processor, '', $paramIndex);

        self::assertEquals('\'10\' NOT BETWEEN "foo"."bar" AND "foo"."baz"', $sql);
    }
}
