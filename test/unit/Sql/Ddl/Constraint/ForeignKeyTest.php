<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\AbstractConstraint;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractConstraint::class, '__construct')]
#[CoversMethod(AbstractConstraint::class, 'setName')]
#[CoversMethod(AbstractConstraint::class, 'getName')]
#[CoversMethod(AbstractConstraint::class, 'setColumns')]
#[CoversMethod(AbstractConstraint::class, 'addColumn')]
#[CoversMethod(AbstractConstraint::class, 'getColumns')]
#[CoversMethod(AbstractConstraint::class, 'toSql')]
#[CoversMethod(ForeignKey::class, '__construct')]
#[CoversMethod(ForeignKey::class, 'setName')]
#[CoversMethod(ForeignKey::class, 'getName')]
#[CoversMethod(ForeignKey::class, 'setReferenceTable')]
#[CoversMethod(ForeignKey::class, 'getReferenceTable')]
#[CoversMethod(ForeignKey::class, 'setReferenceColumn')]
#[CoversMethod(ForeignKey::class, 'getReferenceColumn')]
#[CoversMethod(ForeignKey::class, 'setOnDeleteRule')]
#[CoversMethod(ForeignKey::class, 'getOnDeleteRule')]
#[CoversMethod(ForeignKey::class, 'setOnUpdateRule')]
#[CoversMethod(ForeignKey::class, 'getOnUpdateRule')]
#[CoversMethod(ForeignKey::class, 'toSql')]
final class ForeignKeyTest extends TestCase
{
    public function testSetName(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setName('xxxx');

        // Verify fluent interface
        self::assertSame($fk, $result);

        // Verify the first mutation occurred
        self::assertEquals('xxxx', $fk->getName());

        // Second mutation to verify mutability
        $fk->setName('yyyy');

        // Verify the instance was actually mutated
        self::assertEquals('yyyy', $fk->getName());
    }

    public function testSetReferenceTable(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setReferenceTable('xxxx');

        // Verify fluent interface
        self::assertSame($fk, $result);

        // Verify the first mutation occurred
        self::assertEquals('xxxx', $fk->getReferenceTable());

        // Second mutation to verify mutability
        $fk->setReferenceTable('yyyy');

        // Verify the instance was actually mutated
        self::assertEquals('yyyy', $fk->getReferenceTable());
    }

    public function testSetReferenceColumn(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setReferenceColumn('xxxx');

        // Verify fluent interface
        self::assertSame($fk, $result);

        // Verify the first mutation occurred
        self::assertEquals(['xxxx'], $fk->getReferenceColumn());

        // Second mutation to verify mutability
        $fk->setReferenceColumn('yyyy');

        // Verify the instance was actually mutated
        self::assertEquals(['yyyy'], $fk->getReferenceColumn());
    }

    public function testSetOnDeleteRule(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setOnDeleteRule('CASCADE');

        // Verify fluent interface
        self::assertSame($fk, $result);

        // Verify the first mutation occurred
        self::assertEquals('CASCADE', $fk->getOnDeleteRule());

        // Second mutation to verify mutability
        $fk->setOnDeleteRule('SET NULL');

        // Verify the instance was actually mutated
        self::assertEquals('SET NULL', $fk->getOnDeleteRule());
    }

    public function testSetOnUpdateRule(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setOnUpdateRule('CASCADE');

        // Verify fluent interface
        self::assertSame($fk, $result);

        // Verify the first mutation occurred
        self::assertEquals('CASCADE', $fk->getOnUpdateRule());

        // Second mutation to verify mutability
        $fk->setOnUpdateRule('RESTRICT');

        // Verify the instance was actually mutated
        self::assertEquals('RESTRICT', $fk->getOnUpdateRule());
    }

    public function testGetExpressionData(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam', 'CASCADE', 'SET NULL');

        $processor  = new SqlProcessor(new TrustingSql92Platform());
        $paramIndex = 1;
        $sql        = $fk->toSql($processor, '', $paramIndex);

        self::assertEquals(
            'CONSTRAINT "foo" FOREIGN KEY ("bar") REFERENCES "baz" ("bam") ON DELETE CASCADE ON UPDATE SET NULL',
            $sql
        );
    }
}
