<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;
use function rtrim;

class AlterTable extends AbstractDdl
{
    final public const ADD_COLUMNS = 'addColumns';

    final public const ADD_CONSTRAINTS = 'addConstraints';

    final public const CHANGE_COLUMNS = 'changeColumns';

    final public const DROP_COLUMNS = 'dropColumns';

    final public const DROP_CONSTRAINTS = 'dropConstraints';

    final public const DROP_INDEXES = 'dropIndexes';

    final public const TABLE = 'table';

    protected array $addColumns = [];

    protected array $addConstraints = [];

    protected array $changeColumns = [];

    protected array $dropColumns = [];

    protected array $dropConstraints = [];

    protected array $dropIndexes = [];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        if ($table) {
            $this->setTable($table);
        }
    }

    public function setTable(string|TableIdentifier $name): static
    {
        $this->table = $name;

        return $this;
    }

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->addColumns[] = $column;

        return $this;
    }

    public function changeColumn(string $name, Column\ColumnInterface $column): static
    {
        $this->changeColumns[$name] = $column;

        return $this;
    }

    public function dropColumn(string $name): static
    {
        $this->dropColumns[] = $name;

        return $this;
    }

    public function dropConstraint(string $name): static
    {
        $this->dropConstraints[] = $name;

        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->addConstraints[] = $constraint;

        return $this;
    }

    /**
     * @return static Provides a fluent interface
     */
    public function dropIndex(string $name): static
    {
        $this->dropIndexes[] = $name;

        return $this;
    }

    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::TABLE            => $this->table,
            self::ADD_COLUMNS      => $this->addColumns,
            self::DROP_COLUMNS     => $this->dropColumns,
            self::CHANGE_COLUMNS   => $this->changeColumns,
            self::ADD_CONSTRAINTS  => $this->addConstraints,
            self::DROP_CONSTRAINTS => $this->dropConstraints,
            self::DROP_INDEXES     => $this->dropIndexes,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $decorator = $this instanceof PlatformDecoratorInterface ? $this : null;
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $decorator);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        $sql = "ALTER TABLE " . $processor->resolveTable($this->table) . "\n";

        $clauses = [];

        foreach ($this->addColumns as $column) {
            $clauses[] = 'ADD COLUMN ' . $processor->renderExpression($column);
        }

        foreach ($this->changeColumns as $name => $column) {
            $clauses[] = 'CHANGE COLUMN ' . $platform->quoteIdentifier($name)
                . ' ' . $processor->renderExpression($column);
        }

        foreach ($this->dropColumns as $column) {
            $clauses[] = 'DROP COLUMN ' . $platform->quoteIdentifier($column);
        }

        foreach ($this->addConstraints as $constraint) {
            $clauses[] = 'ADD ' . $processor->renderExpression($constraint);
        }

        foreach ($this->dropConstraints as $constraint) {
            $clauses[] = 'DROP CONSTRAINT ' . $platform->quoteIdentifier($constraint);
        }

        foreach ($this->dropIndexes as $index) {
            $clauses[] = 'DROP INDEX ' . $platform->quoteIdentifier($index);
        }

        $sql .= ' ' . implode(",\n ", $clauses);

        return rtrim($sql, "\n ,");
    }
}
