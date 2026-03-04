<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\NullValue;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use UnexpectedValueException;

use function count;
use function implode;
use function is_string;
use function preg_split;
use function spl_object_id;
use function str_replace;
use function strtr;

use const PREG_SPLIT_DELIM_CAPTURE;

abstract class AbstractSqlRenderer
{
    public const QI_OPEN  = "\x01";
    public const QI_CLOSE = "\x02";
    public const QI_SEP   = "\x02.\x01";
    public const QI_PAIR  = "\x01\x02";

    private const IDENTIFIER_PATTERN
        = '/\b(?!(?:AS|AND|OR|BETWEEN)\b)([a-zA-Z_]\w*+(?:\.[a-zA-Z_]\w*+)*)(?!\s*\()/i';

    /** @var array<class-string, SqlDecoratorInterface> */
    protected array $decorators = [];

    public PlatformInterface $platform;

    public string $quoteChars = '""';

    public ?DriverInterface $driver = null;

    public ?ParameterContainer $parameterContainer = null;

    private string $identifierSeparator = '.';

    /** @var array<int, string> */
    private array $tablePrefixCache = [];

    private string $paramPrefix = '';

    private int $subselectCount = 0;

    /** @var array<string, int> */
    private array $instanceParameterIndex = [];

    private static int $runtimeExpressionPrefix = 0;

    public function init(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): static {
        if (! isset($this->platform) || $this->platform !== $platform) {
            $this->platform            = $platform;
            $this->identifierSeparator = $platform->getIdentifierSeparator();
            $qi                        = $platform->quoteIdentifier('x');
            $this->quoteChars          = $qi[0] . $qi[-1];
            $this->tablePrefixCache    = [];
        }

        $this->driver                 = $driver;
        $this->parameterContainer     = $parameterContainer;
        $this->paramPrefix            = '';
        $this->subselectCount         = 0;
        $this->instanceParameterIndex = [];

        return $this;
    }

    public function getParamPrefix(): string
    {
        return $this->paramPrefix;
    }

    public function setTypeDecorator(string $type, SqlDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }

    public function getTypeDecorator(object $subject): ?SqlDecoratorInterface
    {
        if ($this->decorators === []) {
            return null;
        }

        $subjectClass = $subject::class;
        if (isset($this->decorators[$subjectClass])) {
            return $this->decorators[$subjectClass];
        }

        foreach ($this->decorators as $type => $decorator) {
            if ($subject instanceof $type) {
                return $decorator;
            }
        }

        return null;
    }

    public function renderTableSource(
        TableIdentifier|Select|ExpressionInterface|string|null $table,
        ?string $alias = null,
    ): ?string {
        if ($table === null || $table === '') {
            return null;
        }

        if ($table instanceof TableIdentifier) {
            $rendered = $table->schema === null
                ? self::QI_OPEN . $table->table . self::QI_CLOSE
                : self::QI_OPEN . $table->schema . self::QI_SEP . $table->table . self::QI_CLOSE;
        } elseif ($table instanceof Select) {
            $rendered = '(' . $this->processSubSelect($table) . ')';
        } elseif (is_string($table)) {
            $rendered = self::QI_OPEN . str_replace('.', self::QI_SEP, $table) . self::QI_CLOSE;
        } else {
            $pi       = 0;
            $rendered = $table->toSql($this, '', $pi);
        }

        if ($alias !== null) {
            $rendered .= ' AS ' . self::QI_OPEN . $alias . self::QI_CLOSE;
        }

        return $rendered;
    }

    public function renderResolvedTable(
        TableIdentifier|Select|ExpressionInterface $table,
        ?string $alias = null,
    ): string {
        if (isset($this->tablePrefixCache[$id = spl_object_id($table)])) {
            return $this->tablePrefixCache[$id];
        }

        if ($table instanceof TableIdentifier) {
            $prefix = $alias !== null
                ? self::QI_OPEN . $alias . self::QI_CLOSE
                : ($table->schema !== null
                    ? self::QI_OPEN . $table->schema . self::QI_SEP . $table->table . self::QI_CLOSE
                    : self::QI_OPEN . $table->table . self::QI_CLOSE);
        } else {
            $prefix = self::QI_OPEN . $alias . self::QI_CLOSE;
        }

        return $this->tablePrefixCache[$id] = $prefix . $this->identifierSeparator;
    }

    public function renderIdentifiersIn(string $part): string
    {
        $identifiers = preg_split(self::IDENTIFIER_PATTERN, $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        $count       = count($identifiers);

        for ($idx = 1; $idx < $count; $idx += 2) {
            $identifiers[$idx] = self::QI_OPEN
                . str_replace('.', self::QI_SEP, $identifiers[$idx])
                . self::QI_CLOSE;
        }

        return implode('', $identifiers);
    }

    public function render(ExpressionInterface $expression, ?string $paramPrefix = null): string
    {
        if ($this->parameterContainer === null) {
            $paramIndex = 0;

            return strtr(
                $expression->toSql($this, '', $paramIndex),
                self::QI_PAIR,
                $this->quoteChars
            );
        }

        $paramPrefix = $this->resolveParamPrefix($paramPrefix);
        $paramIndex  = &$this->instanceParameterIndex[$paramPrefix];

        return strtr(
            $expression->toSql($this, $paramPrefix, $paramIndex),
            self::QI_PAIR,
            $this->quoteChars
        );
    }

    public function renderArgument(
        ArgumentInterface $argument,
        string $paramPrefix,
        int &$paramIndex,
    ): string {
        return match (true) {
            $argument instanceof Identifier    => strtr($argument->qi, self::QI_PAIR, $this->quoteChars),
            $argument instanceof Literal       => $argument->literal,
            $argument instanceof NullValue     => 'NULL',
            $argument instanceof Parameter     => $this->bindParameter($argument),
            $argument instanceof Value         => $this->parameterContainer !== null
                ? $this->bindValue($argument->value, $paramPrefix, $paramIndex)
                : $this->platform->quoteValue((string) $argument->value),
            $argument instanceof SelectArgument => $argument->select instanceof Select
                ? '(' . $this->processSubSelect($argument->select) . ')'
                : $argument->select->toSql($this, $paramPrefix, $paramIndex),
            $argument instanceof Values        => $this->renderValues($argument->values, $paramPrefix, $paramIndex),
            $argument instanceof Identifiers   => $this->renderIdentifiers($argument->identifiers),
            default => throw new UnexpectedValueException('Unknown argument type: ' . $argument::class),
        };
    }

    public function bindParameter(Parameter $param, ?string $nameOverride = null): string
    {
        if ($this->parameterContainer instanceof ParameterContainer) {
            $name = $this->paramPrefix . ($nameOverride ?? $param->getPreferredName() ?? 'param');
            $this->parameterContainer->offsetSet($name, $param->getValue(), $param->getTypeHint());

            return $this->driver->formatParameterName($name);
        }

        return $this->platform->quoteValue((string) $param->getValue());
    }

    public function bindValue(
        int|float|string|bool $value,
        string $paramPrefix,
        int &$paramIndex,
    ): string {
        $name = $paramPrefix . $paramIndex++;
        $this->parameterContainer->offsetSet($name, $value);

        return $this->driver->formatParameterName($name);
    }

    public function processSubSelect(Select $subselect): string
    {
        if ($this->parameterContainer instanceof ParameterContainer) {
            $savedPrefix = $this->paramPrefix;

            $this->subselectCount++;
            $this->paramPrefix = 'subselect' . $this->subselectCount;

            $sql = strtr($subselect->buildSqlString($this), self::QI_PAIR, $this->quoteChars);

            $this->paramPrefix = $savedPrefix;
            return $sql;
        }

        return strtr($subselect->buildSqlString($this), self::QI_PAIR, $this->quoteChars);
    }

    /** @param list<Identifier> $identifiers */
    private function renderIdentifiers(array $identifiers): string
    {
        $quoted = [];
        foreach ($identifiers as $id) {
            $quoted[] = strtr($id->qi, self::QI_PAIR, $this->quoteChars);
        }
        return implode(', ', $quoted);
    }

    /** @param list<null|string|int|float|bool> $values */
    private function renderValues(array $values, string $paramPrefix, int &$paramIndex): string
    {
        $rendered = [];
        if ($this->parameterContainer !== null) {
            foreach ($values as $value) {
                $rendered[] = $this->bindValue($value, $paramPrefix, $paramIndex);
            }
        } else {
            foreach ($values as $value) {
                $rendered[] = $this->platform->quoteValue((string) $value);
            }
        }
        return '(' . implode(', ', $rendered) . ')';
    }

    private function resolveParamPrefix(?string $paramPrefix): string
    {
        if ($paramPrefix === null || $paramPrefix === '') {
            $paramPrefix = $this->parameterContainer
                ? 'expr' . self::$runtimeExpressionPrefix++ . 'Param'
                : '';
        } else {
            $paramPrefix = $this->paramPrefix
                . str_replace([' ', "\t", "\n", "\r"], '__', $paramPrefix);
        }

        if (! isset($this->instanceParameterIndex[$paramPrefix])) {
            $this->instanceParameterIndex[$paramPrefix] = 1;
        }

        return $paramPrefix;
    }
}
