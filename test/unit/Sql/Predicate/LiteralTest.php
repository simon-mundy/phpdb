<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDb\Sql\Predicate\Literal;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\TestCase;

class LiteralTest extends TestCase
{
    public function testSetLiteral(): void
    {
        $literal = new Literal('bar');

        // First mutation
        $result = $literal->setLiteral('foo');

        // Verify fluent interface
        self::assertSame($literal, $result);

        // Verify the first mutation occurred
        self::assertEquals('foo', $literal->getLiteral());

        // Second mutation to verify mutability
        $literal->setLiteral('baz');

        // Verify the instance was actually mutated
        self::assertEquals('baz', $literal->getLiteral());
    }

    public function testGetLiteral(): void
    {
        $literal = new Literal('bar');
        self::assertEquals('bar', $literal->getLiteral());
    }

    public function testGetExpressionData(): void
    {
        $literal = new Literal('bar');

        $renderer   = (new Sql92Renderer())->init(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $literal->toSql($renderer, '', $paramIndex);

        self::assertEquals('bar', $sql);
    }
}
