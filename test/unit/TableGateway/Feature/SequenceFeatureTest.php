<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\RuntimeException;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Strategy\StandardSql92;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\SequenceFeature;
use PhpDb\TableGateway\TableGateway;
use PhpDbTest\TableGateway\Feature\TestAsset\SequenceCapablePlatformInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class SequenceFeatureTest extends TestCase
{
    protected SequenceFeature $feature;

    protected TableGateway $tableGateway;

    /**  @var string primary key name */
    protected string $primaryKeyField = 'id';

    /** @var string  sequence name */
    protected static string $sequenceName = 'table_sequence';

    #[Override]
    protected function setUp(): void
    {
        $this->feature = new SequenceFeature($this->primaryKeyField, self::$sequenceName);
    }

    private function createTableGatewayWithSequencePlatform(
        int $sequenceValue = 2
    ): AbstractTableGateway&MockObject {
        $platform = $this->createMock(SequenceCapablePlatformInterface::class);
        $platform->expects($this->any())
            ->method('getSqlStrategy')
            ->willReturn(new StandardSql92());
        $platform->expects($this->any())
            ->method('getNextSequenceValueSql')
            ->willReturnCallback(fn($name) => 'SELECT NEXTVAL(\'"' . $name . '"\')');
        $platform->expects($this->any())
            ->method('getCurrentSequenceValueSql')
            ->willReturnCallback(fn($name) => 'SELECT CURRVAL(\'"' . $name . '"\')');
        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(fn($name) => $name);

        $result = $this->createMock(ResultInterface::class);
        $result->expects($this->any())
            ->method('current')
            ->willReturn(['nextval' => $sequenceValue, 'currval' => $sequenceValue]);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->any())
            ->method('execute')
            ->willReturn($result);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods(['getPlatform', 'createStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);
        $adapter->expects($this->any())
            ->method('createStatement')
            ->willReturn($statement);

        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();

        return $tableGateway;
    }

    private function createTableGatewayWithNonSequencePlatform(): AbstractTableGateway&MockObject
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->any())
            ->method('getSqlStrategy')
            ->willReturn(new StandardSql92());

        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods(['getPlatform', 'createStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);

        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();

        return $tableGateway;
    }

    /**
     * @throws Exception
     */
    public function testNextSequenceId(): void
    {
        $platform = $this->createMock(SequenceCapablePlatformInterface::class);
        $platform->expects($this->any())
            ->method('getSqlStrategy')
            ->willReturn(new StandardSql92());
        $platform->expects($this->once())
            ->method('getNextSequenceValueSql')
            ->with(self::$sequenceName)
            ->willReturn('SELECT NEXTVAL(\'"' . self::$sequenceName . '"\')');

        $result = $this->createMock(ResultInterface::class);
        $result->expects($this->any())
            ->method('current')
            ->willReturn(['nextval' => 2]);
        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->any())
            ->method('execute')
            ->willReturn($result);
        $statement->expects($this->any())
            ->method('prepare')
            ->with('SELECT NEXTVAL(\'"' . self::$sequenceName . '"\')');

        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods(['getPlatform', 'createStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);
        $adapter->expects($this->once())
            ->method('createStatement')
            ->willReturn($statement);

        $this->tableGateway = $this
            ->getMockBuilder(TableGateway::class)
            ->setConstructorArgs(['table', $adapter])
            ->onlyMethods([])
            ->getMock();
        $this->feature->setTableGateway($this->tableGateway);
        $this->feature->nextSequenceId();
    }

    public function testPreInsertWhenPrimaryKeyAlreadyInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform();
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([42, 'test']);

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertEquals(42, $sequenceValueProp->getValue($this->feature));
    }

    public function testPreInsertGeneratesSequenceWhenPrimaryKeyNotInValues(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform(99);
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertEquals(99, $sequenceValueProp->getValue($this->feature));

        $rawState = $insert->getRawState();
        self::assertContains('id', $rawState['columns']);
    }

    public function testPostInsertSetsLastInsertValue(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform(123);
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);
        $this->feature->preInsert($insert);

        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->feature->postInsert($statement, $result);

        self::assertEquals(123, $tableGateway->lastInsertValue);
    }

    public function testLastSequenceId(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform(55);
        $this->feature->setTableGateway($tableGateway);

        $result = $this->feature->lastSequenceId();

        self::assertEquals(55, $result);
    }

    public function testNextSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithNonSequencePlatform();
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Platform does not support sequences');

        $this->feature->nextSequenceId();
    }

    public function testLastSequenceIdThrowsExceptionForUnsupportedPlatform(): void
    {
        $tableGateway = $this->createTableGatewayWithNonSequencePlatform();
        $this->feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Platform does not support sequences');

        $this->feature->lastSequenceId();
    }

    public function testPostInsertDoesNotSetLastInsertValueWhenSequenceValueIsNull(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform();
        $this->feature->setTableGateway($tableGateway);

        $lastInsertValueProp = new ReflectionProperty(AbstractTableGateway::class, 'lastInsertValue');
        $lastInsertValueProp->setValue($tableGateway, 999);

        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->feature->postInsert($statement, $result);

        self::assertEquals(999, $lastInsertValueProp->getValue($tableGateway));
    }

    public function testPreInsertWithPrimaryKeyColumnButNullValue(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform();
        $this->feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['id', 'name']);
        $insert->values([null, 'test']);

        $result = $this->feature->preInsert($insert);

        self::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertNull($sequenceValueProp->getValue($this->feature));
    }

    public function testPreInsertReturnsEarlyWhenNextSequenceIdReturnsNull(): void
    {
        $tableGateway = $this->createTableGatewayWithSequencePlatform();

        $feature = $this->getMockBuilder(SequenceFeature::class)
            ->setConstructorArgs([$this->primaryKeyField, self::$sequenceName])
            ->onlyMethods(['nextSequenceId'])
            ->getMock();

        $feature->expects($this->once())
            ->method('nextSequenceId')
            ->willReturn(null);

        $feature->setTableGateway($tableGateway);

        $insert = new Insert('table');
        $insert->columns(['name']);
        $insert->values(['test']);

        $result = $feature->preInsert($insert);

        self::assertSame($insert, $result);

        $sequenceValueProp = new ReflectionProperty(SequenceFeature::class, 'sequenceValue');
        self::assertNull($sequenceValueProp->getValue($feature));

        $rawState = $insert->getRawState();
        self::assertNotContains('id', $rawState['columns']);
    }
}
