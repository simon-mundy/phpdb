<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Join;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDb\Sql\Where;
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;

use function end;
use function is_array;
use function sprintf;

/**
 * @property AdapterInterface $adapter
 * @property int $lastInsertValue
 * @property string $table
 */
abstract class AbstractTableGateway implements TableGatewayInterface
{
    protected bool $isInitialized = false;

    protected ?AdapterInterface $adapter = null;

    protected TableIdentifier|string|array|null $table = null;

    protected array $columns = [];

    protected ?Feature\FeatureSet $featureSet = null;

    protected ?ResultSetInterface $resultSetPrototype = null;

    protected ?Sql $sql = null;

    protected string|int|false|null $lastInsertValue = null;

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * Initialize
     *
     * @throws Exception\RuntimeException
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        if ($this->featureSet === null) {
            $this->featureSet = new Feature\FeatureSet();
        }

        $this->featureSet->setTableGateway($this);
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, []);

        if ($this->adapter === null) {
            throw new Exception\RuntimeException('This table does not have an Adapter setup');
        }

        if ($this->table === null) {
            throw new Exception\RuntimeException('This table object does not have a valid table set.');
        }

        if ($this->resultSetPrototype === null) {
            $this->resultSetPrototype = new ResultSet();
        }

        if ($this->sql === null) {
            $this->sql = new Sql($this->adapter, $this->table);
        }

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INITIALIZE, []);

        $this->isInitialized = true;
    }

    #[Override]
    public function getTable(): TableIdentifier|array|string
    {
        return $this->table;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFeatureSet(): Feature\FeatureSet
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->featureSet;
    }

    public function getResultSetPrototype(): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->resultSetPrototype;
    }

    public function getSql(): Sql
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->sql;
    }

    #[Override]
    public function select(Where|Closure|string|array|null $where = null): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();

        if ($where instanceof Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        return $this->selectWith($select);
    }

    public function selectWith(Select $select): ResultSetInterface
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeSelect($select);
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function executeSelect(Select $select): ResultSetInterface
    {
        $selectState = $select->getRawState();
        $stateTable  = $selectState['table'] ?? null;
        if (
            $stateTable !== null
            && ! $this->isMatchingTable($stateTable)
        ) {
            throw new Exception\RuntimeException(
                'The table name of the provided Select object must match that of the table'
            );
        }

        if (
            isset($selectState['columns'])
            && $selectState['columns'] === [Select::SQL_STAR]
            && $this->columns !== []
        ) {
            $select->columns($this->columns);
        }

        // apply preSelect features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_SELECT, [$select]);

        // prepare and execute
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        // build result set
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        // apply postSelect features
        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_SELECT, [$statement, $result, $resultSet]);

        return $resultSet;
    }

    #[Override]
    public function insert(array $set): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $insert = $this->sql->insert();
        $insert->values($set);

        return $this->executeInsert($insert);
    }

    public function insertWith(Insert $insert): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeInsert($insert);
    }

    /**
     * @throws Exception\RuntimeException
     * @todo add $columns support
     */
    protected function executeInsert(Insert $insert): int
    {
        $insertState = $insert->getRawState();
        if (! $this->isMatchingTable($insertState['table'])) {
            throw new Exception\RuntimeException(
                'The table name of the provided Insert object must match that of the table'
            );
        }

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_INSERT, [$insert]);

        $statement             = $this->sql->prepareStatementForSqlObject($insert);
        $result                = $statement->execute();
        $this->lastInsertValue = $this->adapter->getDriver()->getConnection()->getLastGeneratedValue();

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_INSERT, [$statement, $result]);

        return $result->getAffectedRows();
    }

    #[Override]
    public function update(
        array $set,
        Where|Closure|array|string|null $where = null,
        ?array $joins = null
    ): int {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $sql    = $this->sql;
        $update = $sql->update();
        $update->set($set);
        if ($where !== null) {
            $update->where($where);
        }

        if ($joins) {
            foreach ($joins as $join) {
                $type = $join['type'] ?? Join::JOIN_INNER;
                $update->join($join['name'], $join['on'], $type);
            }
        }

        return $this->executeUpdate($update);
    }

    public function updateWith(Update $update): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }

        return $this->executeUpdate($update);
    }

    /**
     * @throws Exception\RuntimeException
     * @todo add $columns support
     */
    protected function executeUpdate(Update $update): int
    {
        $updateState = $update->getRawState();
        if (! $this->isMatchingTable($updateState['table'])) {
            throw new Exception\RuntimeException(
                'The table name of the provided Update object must match that of the table'
            );
        }

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_UPDATE, [$update]);

        $statement = $this->sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_UPDATE, [$statement, $result]);

        return $result->getAffectedRows();
    }

    #[Override]
    public function delete(Where|Closure|array|string $where): int
    {
        if (! $this->isInitialized) {
            $this->initialize();
        }
        $delete = $this->sql->delete();
        if ($where instanceof Closure) {
            $where($delete);
        } else {
            $delete->where($where);
        }

        return $this->executeDelete($delete);
    }

    public function deleteWith(Delete $delete): int
    {
        $this->initialize();

        return $this->executeDelete($delete);
    }

    /**
     * @throws Exception\RuntimeException
     * @todo add $columns support
     */
    protected function executeDelete(Delete $delete): int
    {
        $deleteState = $delete->getRawState();
        if (! $this->isMatchingTable($deleteState['table'])) {
            throw new Exception\RuntimeException(
                'The table name of the provided Delete object must match that of the table'
            );
        }

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_PRE_DELETE, [$delete]);

        $statement = $this->sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        $this->featureSet->apply(EventFeatureEventsInterface::EVENT_POST_DELETE, [$statement, $result]);

        return $result->getAffectedRows();
    }

    private function isMatchingTable(mixed $stateTable): bool
    {
        $gatewayTable = $this->table;
        if (is_array($gatewayTable)) {
            $gatewayTable = end($gatewayTable);
        }

        if ($stateTable instanceof TableIdentifier) {
            if ($gatewayTable instanceof TableIdentifier) {
                return $stateTable->table === $gatewayTable->table
                    && $stateTable->schema === $gatewayTable->schema;
            }
            return $stateTable->table === $gatewayTable;
        }

        if ($stateTable === $gatewayTable) {
            return true;
        }

        if (is_array($stateTable)) {
            return end($stateTable) === $gatewayTable;
        }

        return false;
    }

    public function getLastInsertValue(): string|int|false|null
    {
        return $this->lastInsertValue;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $property): mixed
    {
        return match (true) {
            'lastInsertValue' === $property,
            'adapter' === $property,
            'table' === $property => $this->$property,
            $this->featureSet->canCallMagicGet($property) => $this->featureSet->callMagicGet($property),
            default => throw new Exception\InvalidArgumentException(
                'Invalid magic property access in ' . self::class . '::__get()'
            ),
        };
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __set(string $property, mixed $value): void
    {
        if ($this->featureSet->canCallMagicSet($property)) {
            $this->featureSet->callMagicSet($property, $value);

            return;
        }
        throw new Exception\InvalidArgumentException('Invalid magic property access in ' . self::class . '::__set()');
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->featureSet->canCallMagicCall($method)) {
            return $this->featureSet->callMagicCall($method, $arguments);
        }
        throw new Exception\InvalidArgumentException(sprintf(
            'Invalid method (%s) called, caught by %s::__call()',
            $method,
            self::class
        ));
    }

    public function __clone(): void
    {
        $this->resultSetPrototype = isset($this->resultSetPrototype) ? clone $this->resultSetPrototype : null;
        $this->sql                = clone $this->sql;
    }
}
