<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Part\From;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function implode;
use function is_object;
use function range;

class Insert extends AbstractPreparableSql
{
    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    protected From $table;

    /** @var array<string, mixed> Column-to-value mapping (keys are column names) */
    protected array $columns = [];

    protected ?Select $select = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        $this->table = new From();

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
            if ($this->isAssocativeArray($values)) {
                $this->columns = $values;
            } else {
                $i = 0;
                foreach (array_keys($this->columns) as $column) {
                    $this->columns[$column] = $values[$i++] ?? null;
                }
            }
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

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?PlatformDecoratorInterface $decorator = null,
    ): string {
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $decorator);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        $keyword  = $this->getStatementKeyword();
        $tableSql = $processor->resolveTable($this->table->get());

        if ($this->select !== null) {
            $selectSql   = $processor->processSubSelect($this->select);
            $columnNames = array_keys($this->columns);
            if ($columnNames !== []) {
                $columns = [];
                foreach ($columnNames as $col) {
                    $columns[] = $platform->quoteIdentifier($col);
                }
                return $keyword . ' ' . $tableSql
                    . ' (' . implode(', ', $columns) . ') ' . $selectSql;
            }
            return $keyword . ' ' . $tableSql . ' ' . $selectSql;
        }

        if ($this->columns === []) {
            throw new Exception\InvalidArgumentException('values or select should be present');
        }

        $columns           = [];
        $values            = [];
        $i                 = 0;
        $isPdoDriver       = $driver instanceof PdoDriverInterface;
        $paramPrefix       = $this->processInfo['paramPrefix'];
        $hasParamContainer = $parameterContainer instanceof ParameterContainer;

        foreach ($this->columns as $column => $value) {
            $columns[] = $platform->quoteIdentifier($column);

            if ($value === null) {
                $values[] = 'NULL';
            } elseif (! is_object($value)) {
                // Scalar value — most common path
                if ($hasParamContainer) {
                    $name = $paramPrefix
                        . ($isPdoDriver ? 'c_' . $i++ : $column);
                    $parameterContainer->offsetSet($name, $value);
                    $values[] = $driver->formatParameterName($name);
                } else {
                    $values[] = $platform->quoteValue((string) $value);
                }
            } elseif ($value instanceof ArgumentInterface) {
                $values[] = match ($value->getType()) {
                    ArgumentType::Parameter => $processor->renderParameter(
                        $value,
                        $isPdoDriver ? 'c_' . $i++ : null,
                    ),
                    ArgumentType::Select => $processor->renderExpression(
                        $value->getValue(),
                    ),
                    ArgumentType::Literal => $value->getValue(),
                    ArgumentType::Null    => 'NULL',
                    default => $platform->quoteValue(
                        (string) $value->getValue(),
                    ),
                };
            } elseif ($value instanceof Select) {
                $values[] = '(' . $processor->processSubSelect($value) . ')';
            } else {
                $values[] = $processor->renderExpression($value);
            }
        }

        return $keyword . ' ' . $tableSql
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';
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
