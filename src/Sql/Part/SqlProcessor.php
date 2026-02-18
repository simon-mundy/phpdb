<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use ValueError;

use function implode;
use function str_replace;

/**
 * Central SQL rendering service used by Part objects and Expressions
 * to render arguments, resolve tables/columns, and bind parameters.
 */
class SqlProcessor
{
    private string $paramPrefix = '';

    private int $subselectCount = 0;

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
        return $this->paramPrefix;
    }

    public function setParamPrefix(string $prefix): void
    {
        $this->paramPrefix = $prefix;
    }

    /**
     * Render a Parameter argument: bind to parameterContainer if available, otherwise quote inline.
     *
     * @param Parameter    $param         The parameter to render
     * @param ?string      $nameOverride  Override the parameter's preferred name (e.g. for PDO incremental naming)
     */
    public function renderParameter(Parameter $param, ?string $nameOverride = null): string
    {
        if ($this->parameterContainer instanceof ParameterContainer) {
            $name = $this->paramPrefix . ($nameOverride ?? $param->getPreferredName() ?? 'param');
            $this->parameterContainer->offsetSet($name, $param->getValue(), $param->getTypeHint());

            return $this->driver->formatParameterName($name);
        }

        return $this->platform->quoteValue((string) $param->getValue());
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
            $this->subselectCount++;
            $processInfoContext->processInfo['subselectCount'] = $this->subselectCount;
            $processInfoContext->processInfo['paramPrefix']    = 'subselect'
                . $processInfoContext->processInfo['subselectCount'];

            $sql = $decorator->buildSqlString(
                $this->platform,
                $this->driver,
                $this->parameterContainer
            );
            $this->subselectCount = $decorator->processInfo['subselectCount'];

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
     * Render table with optional alias: "table" AS "alias"
     */
    public function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    /**
     * Render an ExpressionInterface directly via its renderSql() method.
     * Sets up parameter prefix and index, then delegates to the expression.
     */
    public function renderExpression(
        ExpressionInterface $expression,
        ?string $namedParameterPrefix = null
    ): string {
        $namedParameterPrefix = $this->resolveParamPrefix($namedParameterPrefix);
        $paramIndex           = &$this->instanceParameterIndex[$namedParameterPrefix];

        return $expression->renderSql($this, $namedParameterPrefix, $paramIndex);
    }

    /**
     * Render a single ArgumentInterface to its SQL representation.
     * Centralises type dispatch so expressions can render arguments
     * without knowing about quoting, binding, or subselect handling.
     */
    public function renderArgument(
        ArgumentInterface $argument,
        string $paramPrefix,
        int &$paramIndex,
    ): string {
        return match (true) {
            $argument instanceof Value => $this->parameterContainer instanceof ParameterContainer
                ? $this->processExpressionParameterName(
                    $argument->getValue(),
                    $paramPrefix,
                    $paramIndex,
                )
                : $this->platform->quoteValue((string) $argument->getValue()),
            $argument instanceof Identifier => $this->platform->quoteIdentifierInFragment($argument->getValue()),
            $argument instanceof Literal => $argument->getValue(),
            $argument instanceof Values => $this->renderValuesArgument($argument, $paramPrefix, $paramIndex),
            $argument instanceof Identifiers => $this->processIdentifiersArgument($argument),
            $argument instanceof SelectArgument => $this->renderSelectArgument($argument, $paramPrefix, $paramIndex),
            default => throw new Exception\InvalidArgumentException('Unknown argument type'),
        };
    }

    /**
     * Render a Values argument as a parenthesised comma-separated list: (val1, val2, val3)
     */
    private function renderValuesArgument(Values $argument, string $paramPrefix, int &$paramIndex): string
    {
        $values          = $argument->getValue();
        $processedValues = [];

        if ($this->parameterContainer instanceof ParameterContainer) {
            foreach ($values as $value) {
                $processedValues[] = $this->processExpressionParameterName($value, $paramPrefix, $paramIndex);
            }
        } else {
            foreach ($values as $value) {
                $processedValues[] = $this->platform->quoteValue((string) $value);
            }
        }

        return '(' . implode(', ', $processedValues) . ')';
    }

    /**
     * Render a SelectArgument: wraps Select in (...), renders ExpressionInterface via renderSql().
     */
    private function renderSelectArgument(SelectArgument $argument, string $paramPrefix, int &$paramIndex): string
    {
        $value = $argument->getValue();

        if ($value instanceof Select) {
            return '(' . $this->processSubSelect($value) . ')';
        }

        if ($value instanceof ExpressionInterface) {
            return $value->renderSql($this, $paramPrefix, $paramIndex);
        }

        throw new \ValueError('Invalid SelectArgument value');
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

    /**
     * Resolve and initialize the named parameter prefix for expression rendering.
     */
    private function resolveParamPrefix(?string $namedParameterPrefix): string
    {
        if ($namedParameterPrefix === null || $namedParameterPrefix === '') {
            $namedParameterPrefix = $this->parameterContainer
                ? 'expr' . self::$runtimeExpressionPrefix++ . 'Param'
                : '';
        } else {
            $namedParameterPrefix = $this->paramPrefix
                . str_replace([' ', "\t", "\n", "\r"], '__', $namedParameterPrefix);
        }

        if (! isset($this->instanceParameterIndex[$namedParameterPrefix])) {
            $this->instanceParameterIndex[$namedParameterPrefix] = 1;
        }

        return $namedParameterPrefix;
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
