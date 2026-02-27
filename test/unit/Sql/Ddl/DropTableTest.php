<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(DropTable::class, '__construct')]
#[CoversMethod(DropTable::class, 'ifExists')]
#[CoversMethod(DropTable::class, 'getIfExists')]
#[CoversMethod(DropTable::class, 'getRawState')]
#[CoversMethod(DropTable::class, 'getSqlString')]
#[CoversMethod(DropTable::class, 'processTable')]
class DropTableTest extends TestCase
{
    public function testGetSqlString(): void
    {
        $dt = new DropTable('foo');
        self::assertEquals('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('foo'));
        self::assertEquals('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('bar', 'foo'));
        self::assertEquals('DROP TABLE "foo"."bar"', $dt->getSqlString());
    }

    public function testIfExists(): void
    {
        $dt = new DropTable('foo');
        self::assertFalse($dt->getIfExists());

        $result = $dt->ifExists();
        self::assertSame($dt, $result);
        self::assertTrue($dt->getIfExists());

        self::assertEquals('DROP TABLE IF EXISTS "foo"', $dt->getSqlString());
    }

    public function testIfExistsDisable(): void
    {
        $dt = new DropTable('foo');
        $dt->ifExists();
        self::assertTrue($dt->getIfExists());

        $dt->ifExists(false);
        self::assertFalse($dt->getIfExists());

        self::assertEquals('DROP TABLE "foo"', $dt->getSqlString());
    }

    public function testIfExistsWithTableIdentifier(): void
    {
        $dt = new DropTable(new TableIdentifier('bar', 'foo'));
        $dt->ifExists();

        self::assertEquals('DROP TABLE IF EXISTS "foo"."bar"', $dt->getSqlString());
    }

    public function testGetRawState(): void
    {
        $dt = new DropTable('foo');

        $rawState = $dt->getRawState();

        self::assertIsArray($rawState);
        self::assertArrayHasKey(DropTable::TABLE, $rawState);
        self::assertArrayHasKey(DropTable::IF_EXISTS, $rawState);
        self::assertEquals('foo', $rawState[DropTable::TABLE]);
        self::assertEquals('', $rawState[DropTable::IF_EXISTS]);
    }

    public function testGetRawStateWithIfExists(): void
    {
        $dt = new DropTable('foo');
        $dt->ifExists();

        $rawState = $dt->getRawState();

        self::assertEquals('IF EXISTS', $rawState[DropTable::IF_EXISTS]);
    }

    public function testGetRawStateByKey(): void
    {
        $dt = new DropTable('foo');

        self::assertEquals('foo', $dt->getRawState(DropTable::TABLE));
        self::assertEquals('', $dt->getRawState(DropTable::IF_EXISTS));
    }
}
