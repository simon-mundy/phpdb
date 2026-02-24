<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Strategy;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Select;
use PhpDb\Sql\Strategy\AbstractSqlStrategy;
use PhpDb\Sql\Strategy\StandardSql92;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractSqlStrategy::class)]
final class SqlStrategyTest extends TestCase
{
    public function testGetTypeDecoratorReturnsSubjectWhenNoDecoratorRegistered(): void
    {
        $strategy = new StandardSql92();
        $select   = new Select('foo');

        $result = $strategy->getTypeDecorator($select);

        self::assertSame($select, $result);
    }

    public function testGetTypeDecoratorReturnsDecoratorWhenRegistered(): void
    {
        $strategy  = new StandardSql92();
        $decorator = new TestAsset\SelectDecorator();
        $strategy->setTypeDecorator(Select::class, $decorator);

        $select = new Select('foo');
        $result = $strategy->getTypeDecorator($select);

        self::assertSame($decorator, $result);
    }

    public function testGetDecoratorsReturnsAllRegistered(): void
    {
        $strategy  = new StandardSql92();
        $decorator = new TestAsset\SelectDecorator();
        $strategy->setTypeDecorator(Select::class, $decorator);

        $decorators = $strategy->getDecorators();

        self::assertCount(1, $decorators);
        self::assertSame($decorator, $decorators[Select::class]);
    }

    public function testBuildSqlStringViaAdapter(): void
    {
        $platform = new TestAsset\TrustingStandardPlatform();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturn('?');
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturnCallback(fn(): StatementContainer => new StatementContainer());

        $adapter = new Adapter($mockDriver, $platform, new ResultSet());

        $strategy = $adapter->getPlatform()->getSqlStrategy();
        self::assertInstanceOf(StandardSql92::class, $strategy);
    }
}
