<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\InsertSelect;
use PhpDb\Sql\Part\InsertValues;
use PhpDb\Sql\Part\PartInterface;
use PhpDb\Sql\Part\SqlPartProcessor;
use PhpDb\Sql\Part\Table;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function array_combine;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function range;

class Insert extends AbstractPreparableSql
{
    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    protected Table $table;

    /** @var array<string, mixed> Column-to-value mapping (keys are column names) */
    protected array $columns = [];

    protected ?Select $select = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new Table();

        if ($table) {
            $this->into($table);
        }
    }

    /**
     * Create INTO clause
     */
    public function into(TableIdentifier|string|array $table): static
    {
        $this->table->set($table);
        return $this;
    }

    /**
     * Specify columns
     */
    public function columns(array $columns): static
    {
        $this->columns = array_flip($columns);
        return $this;
    }

    /**
     * Specify values to insert
     *
     * @param string $flag one of VALUES_MERGE or VALUES_SET; defaults to VALUES_SET
     * @throws Exception\InvalidArgumentException
     */
    public function values(array|Select $values, string $flag = self::VALUES_SET): static
    {
        if ($values instanceof Select) {
            if ($flag === self::VALUES_MERGE) {
                throw new Exception\InvalidArgumentException(
                    'A PhpDb\Sql\Select instance cannot be provided with the merge flag'
                );
            }

            $this->select = $values;
            return $this;
        }

        if ($this->select !== null && $flag === self::VALUES_MERGE) {
            throw new Exception\InvalidArgumentException(
                'An array of values cannot be provided with the merge flag when a PhpDb\Sql\Select'
                . ' instance already exists as the value source'
            );
        }

        if ($flag === self::VALUES_SET) {
            $this->columns = $this->isAssocativeArray($values)
                ? $values
                : array_combine(array_keys($this->columns), array_values($values));
        } else {
            foreach ($values as $column => $value) {
                $this->columns[$column] = $value;
            }
        }

        return $this;
    }

    /**
     * Simple test for an associative array
     *
     * @link http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
     */
    private function isAssocativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Create INTO SELECT clause
     */
    public function select(Select $select): static
    {
        return $this->values($select);
    }

    /**
     * Get raw state
     */
    public function getRawState(?string $key = null): TableIdentifier|string|array
    {
        $rawState = [
            'table'   => $this->table->get(),
            'columns' => array_keys($this->columns),
            'values'  => array_values($this->columns),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Get the statement keyword (e.g. "INSERT INTO").
     * Override in subclasses for variants like "REPLACE INTO" or "INSERT IGNORE INTO".
     */
    protected function getStatementKeyword(): string
    {
        return 'INSERT INTO';
    }

    /** @return PartInterface[] */
    protected function getParts(): array
    {
        $keyword = $this->getStatementKeyword();

        $insertValues = new InsertValues($this->table);
        $insertValues->setKeyword($keyword);
        $insertValues->setColumns($this->columns);
        $insertValues->setHasSelect($this->select !== null);

        $insertSelect = new InsertSelect($this->table);
        $insertSelect->setKeyword($keyword);
        $insertSelect->setSelect($this->select);
        $insertSelect->setColumns($this->columns);

        return [
            $insertValues,
            $insertSelect,
        ];
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $decorator = $this instanceof PlatformDecoratorInterface ? $this : null;
        $processor = new SqlPartProcessor($platform, $driver, $parameterContainer, $decorator);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        $sqls = [];
        foreach ($this->getParts() as $part) {
            $sql = $part->toSql($processor);
            if ($sql !== null) {
                $sqls[] = $sql;
            }
        }

        return implode(' ', $sqls);
    }

    /**
     * Overloading: variable setting
     *
     * Proxies to values, using VALUES_MERGE strategy
     */
    public function __set(string $name, mixed $value): void
    {
        $this->columns[$name] = $value;
    }

    /**
     * Overloading: variable unset
     *
     * Proxies to values and columns
     *
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public function __unset(string $name)
    {
        if (! array_key_exists($name, $this->columns)) {
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        unset($this->columns[$name]);
    }

    /**
     * Overloading: variable isset
     *
     * Proxies to columns; does a column of that name exist?
     *
     * @return bool
     */
    public function __isset(string $name)
    {
        return array_key_exists($name, $this->columns);
    }

    /**
     * Overloading: variable retrieval
     * Retrieves value by column name
     *
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function __get(string $name): mixed
    {
        if (! array_key_exists($name, $this->columns)) {
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        return $this->columns[$name];
    }
}
