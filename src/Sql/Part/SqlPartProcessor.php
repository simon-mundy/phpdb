<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use ValueError;

use function count;
use function implode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function str_replace;
use function strtoupper;
use function vsprintf;

/**
 * Shared processing service used by all Part objects to render expressions,
 * resolve tables/columns, and bind parameters.
 */
class SqlPartProcessor
{
    /** @var array{paramPrefix: string, subselectCount: int} */
    private array $processInfo = ['paramPrefix' => '', 'subselectCount' => 0];

    private array $instanceParameterIndex = [];

    private static int $runtimeExpressionPrefix = 0;

    public function __construct(
        public readonly PlatformInterface $platform,
        public readonly ?DriverInterface $driver = null,
        public readonly ?ParameterContainer $parameterContainer = null,
        private ?PlatformDecoratorInterface $decorator = null,
    ) {
    }

    public function getParamPrefix(): string
    {
        return $this->processInfo['paramPrefix'];
    }

    public function setParamPrefix(string $prefix): void
    {
        $this->processInfo['paramPrefix'] = $prefix;
    }

    /**
     * Render an ExpressionInterface to a SQL string, handling parameter binding.
     */
    public function processExpression(
        ExpressionInterface $expression,
        ?string $namedParameterPrefix = null
    ): string {
        $expressionData   = $expression->getExpressionData();
        $specification    = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ($expressionValues === []) {
            return str_replace('%%', '%', $specification);
        }

        if ($namedParameterPrefix === null || $namedParameterPrefix === '') {
            $namedParameterPrefix = $this->parameterContainer
                ? 'expr' . self::$runtimeExpressionPrefix++ . 'Param'
                : '';
        } else {
            $namedParameterPrefix = $this->processInfo['paramPrefix']
                . str_replace([' ', "\t", "\n", "\r"], '__', $namedParameterPrefix);
        }

        if (! isset($this->instanceParameterIndex[$namedParameterPrefix])) {
            $this->instanceParameterIndex[$namedParameterPrefix] = 1;
        }

        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];
        $expressionValues     = $this->flattenExpressionValues($expressionValues);
        $values               = [];

        foreach ($expressionValues as $vIndex => $argument) {
            $values[] = match (true) {
                $argument instanceof Value => $this->parameterContainer instanceof ParameterContainer
                    ? $this->processExpressionParameterName(
                        $argument->getValue(),
                        $namedParameterPrefix,
                        $expressionParamIndex,
                    )
                    : $this->platform->quoteValue((string) $argument->getValue()),
                $argument instanceof Identifier => $this->platform->quoteIdentifierInFragment($argument->getValue()),
                $argument instanceof Literal => $argument->getValue(),
                $argument instanceof Values => $this->processValuesArgument(
                    $argument,
                    $expressionParamIndex,
                    $namedParameterPrefix,
                ),
                $argument instanceof Identifiers => $this->processIdentifiersArgument($argument),
                $argument instanceof SelectArgument => $this->processExpressionOrSelect(
                    $argument,
                    $namedParameterPrefix,
                    $vIndex,
                ),
                default => throw new Exception\InvalidArgumentException('Unknown argument type'),
            };
        }

        return vsprintf($specification, $values);
    }

    /**
     * Render a subselect, handling decorator propagation and param prefix tracking.
     */
    public function processSubSelect(Select $subselect): string
    {
        if ($this->decorator !== null) {
            $decorator = clone $this->decorator;
            $decorator->setSubject($subselect);
        } else {
            $decorator = $subselect;
        }

        if ($this->parameterContainer instanceof ParameterContainer) {
            $processInfoContext = $decorator instanceof PlatformDecoratorInterface ? $subselect : $decorator;
            $this->processInfo['subselectCount']++;
            $processInfoContext->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
            $processInfoContext->processInfo['paramPrefix']    = 'subselect'
                . $processInfoContext->processInfo['subselectCount'];

            $sql                                     = $decorator->buildSqlString(
                $this->platform,
                $this->driver,
                $this->parameterContainer
            );
            $this->processInfo['subselectCount'] = $decorator->processInfo['subselectCount'];

            return $sql;
        }

        return $decorator->buildSqlString($this->platform, $this->driver, $this->parameterContainer);
    }

    /**
     * Quote and resolve a table reference (handles TableIdentifier, Select subquery, and string).
     */
    public function resolveTable(Select|string|TableIdentifier|null $table): string|null
    {
        $schema = null;
        if ($table instanceof TableIdentifier) {
            [$table, $schema] = $table->getTableAndSchema();
        }

        if ($table instanceof Select) {
            $table = '(' . $this->processSubSelect($table) . ')';
        } elseif ($table) {
            $table = $this->platform->quoteIdentifier($table);
        }

        if ($schema && $table) {
            $table = $this->platform->quoteIdentifier($schema) . $this->platform->getIdentifierSeparator() . $table;
        }

        return $table;
    }

    /**
     * Resolve a column value (identifier, expression, subselect, literal, or null).
     */
    public function resolveColumnValue(
        Select|array|string|int|bool|ExpressionInterface|null $column,
        ?string $namedParameterPrefix = null
    ): string {
        $namedParameterPrefix = $namedParameterPrefix
            ? $this->processInfo['paramPrefix'] . $namedParameterPrefix
            : $namedParameterPrefix;
        $isIdentifier         = false;
        $fromTable            = '';
        if (is_array($column)) {
            $isIdentifier = (bool) ($column['isIdentifier'] ?? false);
            $fromTable    = $column['fromTable'] ?? '';
            $column       = $column['column'];
        }

        if ($column instanceof ExpressionInterface) {
            return $this->processExpression($column, $namedParameterPrefix);
        }

        if ($column instanceof Select) {
            return '(' . $this->processSubSelect($column) . ')';
        }

        if ($column === null) {
            return 'NULL';
        }

        return $isIdentifier
            ? $fromTable . $this->platform->quoteIdentifierInFragment($column)
            : $this->platform->quoteValue($column);
    }

    /**
     * Render table with optional alias: "table" AS "alias"
     */
    public function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    /**
     * @param ArgumentInterface[] $arguments
     * @return ArgumentInterface[]
     */
    private function flattenExpressionValues(array $arguments): array
    {
        $hasValues = false;
        foreach ($arguments as $argument) {
            if ($argument instanceof Values) {
                $hasValues = true;
                break;
            }
        }

        if (! $hasValues) {
            return $arguments;
        }

        $values = [];
        foreach ($arguments as $argument) {
            if ($argument instanceof Values) {
                foreach ($argument->getValue() as $v) {
                    $values[] = new Value($v);
                }
            } else {
                $values[] = $argument;
            }
        }

        return $values;
    }

    private function processExpressionOrSelect(
        ArgumentInterface $argument,
        string $namedParameterPrefix,
        int $vIndex,
    ): string {
        $value = $argument->getValue();

        return match (true) {
            $value instanceof Select => '('
                . $this->processSubSelect($value)
                . ')',
            $value instanceof ExpressionInterface => $this->processExpression(
                $value,
                "{$namedParameterPrefix}{$vIndex}subpart"
            ),
            default => throw new ValueError('Invalid Argument type'),
        };
    }

    private function processValuesArgument(
        ArgumentInterface $argument,
        int &$expressionParamIndex,
        string $namedParameterPrefix,
    ): string {
        $values          = $argument->getValue();
        $processedValues = [];

        if ($this->parameterContainer instanceof ParameterContainer) {
            foreach ($values as $value) {
                $processedValues[] = $this->processExpressionParameterName(
                    $value,
                    $namedParameterPrefix,
                    $expressionParamIndex,
                );
            }
        } else {
            foreach ($values as $value) {
                $processedValues[] = $this->platform->quoteValue((string) $value);
            }
        }

        return implode(', ', $processedValues);
    }

    private function processIdentifiersArgument(ArgumentInterface $argument): string
    {
        $identifiers          = $argument->getValue();
        $processedIdentifiers = [];

        foreach ($identifiers as $identifier) {
            $processedIdentifiers[] = $this->platform->quoteIdentifierInFragment($identifier);
        }

        return implode(', ', $processedIdentifiers);
    }

    private function processExpressionParameterName(
        int|float|string|bool $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
    ): ?string {
        $name = $namedParameterPrefix . $expressionParamIndex++;
        $this->parameterContainer->offsetSet($name, $value);

        return $this->driver->formatParameterName($name);
    }
}
