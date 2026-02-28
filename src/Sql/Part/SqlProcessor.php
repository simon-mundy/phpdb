<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function count;
use function implode;
use function is_string;
use function preg_split;
use function str_replace;
use function strpos;
use function substr;

use const PREG_SPLIT_DELIM_CAPTURE;

class SqlProcessor
{
    private const IDENTIFIER_PATTERN
        = '/\b(?!(?:AS|AND|OR|BETWEEN)\b)([a-zA-Z_]\w*+(?:\.[a-zA-Z_]\w*+)*)(?!\s*\()/i';

    private string $paramPrefix = '';

    private int $subselectCount = 0;

    private array $instanceParameterIndex = [];

    private static int $runtimeExpressionPrefix = 0;

    public readonly string $identifierSeparator;

    public function __construct(
        public readonly PlatformInterface $platform,
        public readonly ?DriverInterface $driver = null,
        public readonly ?ParameterContainer $parameterContainer = null,
        private ?SqlPlatform $sqlPlatform = null,
    ) {
        $this->identifierSeparator = $platform->getIdentifierSeparator();
    }

    public function getParamPrefix(): string
    {
        return $this->paramPrefix;
    }

    public function setParamPrefix(string $prefix): void
    {
        $this->paramPrefix = $prefix;
    }

    public function renderParameter(Parameter $param, ?string $nameOverride = null): string
    {
        if ($this->parameterContainer instanceof ParameterContainer) {
            $name = $this->paramPrefix . ($nameOverride ?? $param->getPreferredName() ?? 'param');
            $this->parameterContainer->offsetSet($name, $param->getValue(), $param->getTypeHint());

            return $this->driver->formatParameterName($name);
        }

        return $this->platform->quoteValue((string) $param->getValue());
    }

    public function processSubSelect(Select $subselect): string
    {
        if ($this->parameterContainer instanceof ParameterContainer) {
            $this->subselectCount++;
            $subselect->processInfo['subselectCount'] = $this->subselectCount;
            $subselect->processInfo['paramPrefix']    = 'subselect'
                . $subselect->processInfo['subselectCount'];

            $sql                  = $subselect->buildSqlString(
                $this->platform,
                $this->driver,
                $this->parameterContainer,
                $this->sqlPlatform,
            );
            $this->subselectCount = $subselect->processInfo['subselectCount'];

            return $sql;
        }

        return $subselect->buildSqlString(
            $this->platform,
            $this->driver,
            $this->parameterContainer,
            $this->sqlPlatform,
        );
    }

    public function resolveTable(Select|string|TableIdentifier|null $table): string|null
    {
        if ($table === null || $table === '') {
            return $table;
        }

        if (! $table instanceof TableIdentifier) {
            $table = new TableIdentifier($table);
        }

        return $this->resolveTableRef($table);
    }

    public function resolveTableWithAlias(TableIdentifier $ref): string
    {
        $resolved = $this->resolveTableRef($ref);

        if ($ref->getAlias() !== null) {
            $resolved .= ' AS ' . $this->platform->quoteIdentifier($ref->getAlias());
        }

        return $resolved;
    }

    public function getQuotedPrefix(TableIdentifier $ref): string
    {
        if ($ref->getAlias() !== null) {
            return $this->platform->quoteIdentifier($ref->getAlias())
                . $this->identifierSeparator;
        }

        return $this->resolveTableRef($ref) . $this->identifierSeparator;
    }

    private function resolveTableRef(TableIdentifier $ref): string
    {
        $table = $ref->getTable();

        if (is_string($table)) {
            return $this->platform->quoteIdentifier(name: $table, prefix: $ref->getSchema());
        }

        if ($table instanceof Select) {
            return '(' . $this->processSubSelect($table) . ')';
        }

        $pi = 0;
        return $table->toSql($this, '', $pi);
    }

    public function renderQuotedIdentifiers(string $part): string
    {
        $identifiers = preg_split(self::IDENTIFIER_PATTERN, $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        $count       = count($identifiers);

        for ($idx = 1; $idx < $count; $idx += 2) {
            $dot = strpos($identifiers[$idx], '.');
            if ($dot !== false) {
                $identifiers[$idx] = '"' . substr($identifiers[$idx], 0, $dot) . '"."' . substr(
                    $identifiers[$idx],
                    $dot + 1
                ) . '"';
            } else {
                $identifiers[$idx] = '"' . $identifiers[$idx] . '"';
            }
        }

        return implode('', $identifiers);
    }

    public function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    public function renderExpression(
        ExpressionInterface $expression,
        ?string $namedParameterPrefix = null
    ): string {
        if ($this->parameterContainer === null) {
            $paramIndex = 0;

            return $expression->toSql($this, '', $paramIndex);
        }

        $namedParameterPrefix = $this->resolveParamPrefix($namedParameterPrefix);
        $paramIndex           = &$this->instanceParameterIndex[$namedParameterPrefix];

        return $expression->toSql($this, $namedParameterPrefix, $paramIndex);
    }

    public function renderArgument(
        ArgumentInterface $argument,
        string $paramPrefix,
        int &$paramIndex,
    ): string {
        return $argument->render($this, $paramPrefix, $paramIndex);
    }

    public function bindValue(
        int|float|string|bool $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
    ): string {
        $name = $namedParameterPrefix . $expressionParamIndex++;
        $this->parameterContainer->offsetSet($name, $value);

        return $this->driver->formatParameterName($name);
    }

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
}
