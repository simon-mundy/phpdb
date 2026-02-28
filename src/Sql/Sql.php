<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;

use function sprintf;

class Sql
{
    protected AdapterInterface $adapter;

    protected TableIdentifier|string|array|null $table;

    protected Platform\AbstractPlatform $sqlPlatform;

    public function __construct(
        AdapterInterface $adapter,
        array|string|TableIdentifier|null $table = null
    ) {
        $this->adapter     = $adapter;
        $this->table       = $table;
        $this->sqlPlatform = new Platform\Sql92Platform();
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function hasTable(): bool
    {
        return $this->table !== null;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setTable(array|string|TableIdentifier $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): array|string|TableIdentifier|null
    {
        return $this->table;
    }

    public function getSqlPlatform(): ?Platform\AbstractPlatform
    {
        return $this->sqlPlatform;
    }

    public function select(string|TableIdentifier|null $table = null): Select
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Select($table ?: $this->table);
    }

    public function insert(string|null|TableIdentifier $table = null): Insert
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Insert($table ?: $this->table);
    }

    public function update(null|string|TableIdentifier $table = null): Update
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Update($table ?: $this->table);
    }

    public function delete(null|string|TableIdentifier $table = null): Delete
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new Delete($table ?: $this->table);
    }

    public function prepareStatementForSqlObject(
        AbstractPreparableSql $sqlObject,
        ?StatementInterface $statement = null,
        ?AdapterInterface $adapter = null
    ): StatementInterface {
        $adapter   ??= $this->adapter;
        $statement ??= $adapter->getDriver()->createStatement();

        $parameterContainer = $statement->getParameterContainer();
        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();
            $statement->setParameterContainer($parameterContainer);
        }

        $statement->setSql(
            $sqlObject->buildSqlString(
                $adapter->getPlatform(),
                $adapter->getDriver(),
                $parameterContainer,
                $this->sqlPlatform,
            )
        );

        return $statement;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function buildSqlString(AbstractSql $sqlObject, ?AdapterInterface $adapter = null): string
    {
        $platform = ($adapter ?? $this->adapter)->getPlatform();

        return $sqlObject->buildSqlString($platform, null, null, $this->sqlPlatform);
    }
}
