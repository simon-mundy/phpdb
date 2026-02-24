<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\SequenceCapableInterface;
use PhpDb\Exception\RuntimeException;
use PhpDb\Sql\Insert;

use function array_search;

class SequenceFeature extends AbstractFeature
{
    protected string $primaryKeyField;

    protected string $sequenceName;

    protected ?int $sequenceValue = null;

    public function __construct(string $primaryKeyField, string $sequenceName)
    {
        $this->primaryKeyField = $primaryKeyField;
        $this->sequenceName    = $sequenceName;
    }

    public function preInsert(Insert $insert): Insert
    {
        $columns = $insert->getRawState('columns');
        $values  = $insert->getRawState('values');
        $key     = array_search($this->primaryKeyField, $columns);
        if ($key !== false) {
            $this->sequenceValue = $values[$key] ?? null;
            return $insert;
        }

        $this->sequenceValue = $this->nextSequenceId();
        if ($this->sequenceValue === null) {
            return $insert;
        }

        $insert->values([$this->primaryKeyField => $this->sequenceValue], Insert::VALUES_MERGE);
        return $insert;
    }

    public function postInsert(StatementInterface $statement, ResultInterface $result): void
    {
        if ($this->sequenceValue !== null) {
            $this->tableGateway->lastInsertValue = $this->sequenceValue;
        }
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     *
     * @throws RuntimeException
     */
    public function nextSequenceId(): ?int
    {
        $platform = $this->tableGateway->adapter->getPlatform();
        if (! $platform instanceof SequenceCapableInterface) {
            throw new RuntimeException('Platform does not support sequences');
        }

        $sql = $platform->getNextSequenceValueSql($this->sequenceName);

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result   = $statement->execute();
        $sequence = $result->current();
        unset($statement, $result);
        return $sequence['nextval'];
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     *
     * @throws RuntimeException
     */
    public function lastSequenceId(): int
    {
        $platform = $this->tableGateway->adapter->getPlatform();
        if (! $platform instanceof SequenceCapableInterface) {
            throw new RuntimeException('Platform does not support sequences');
        }

        $sql = $platform->getCurrentSequenceValueSql($this->sequenceName);

        $statement = $this->tableGateway->adapter->createStatement();
        $statement->prepare($sql);
        $result   = $statement->execute();
        $sequence = $result->current();
        unset($statement, $result);
        return $sequence['currval'];
    }
}
