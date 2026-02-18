<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\Part\SqlProcessor;

use function array_slice;
use function array_unique;
use function count;
use function func_get_args;
use function func_num_args;
use function is_array;
use function preg_match_all;
use function str_replace;
use function vsprintf;

class Expression extends AbstractExpression
{
    /**
     * @const
     */
    final public const PLACEHOLDER = '?';

    protected string $expression = '';

    /** @var ArgumentInterface[] */
    protected array $parameters = [];

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     */
    public function __construct(
        string $expression = '',
        null|bool|string|float|int|array|ArgumentInterface|ExpressionInterface $parameters = []
    ) {
        if ($expression !== '') {
            $this->setExpression($expression);
        }

        if (func_num_args() > 2) {
            /**
             * @deprecated
             *
             * @todo Make notes in documentation
             */
            $parameters = array_slice(func_get_args(), 1);
        }

        $this->setParameters($parameters);
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setExpression(string $expression): self
    {
        if ($expression === '') {
            throw new Exception\InvalidArgumentException('Supplied expression must not be an empty string.');
        }

        $this->expression = $expression;
        return $this;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setParameters(
        null|bool|string|float|int|array|ExpressionInterface|ArgumentInterface $parameters = []
    ): self {
        if (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        foreach ($parameters as $parameter) {
            if (is_array($parameter)) {
                $parameter = new Values($parameter);
            } elseif ($parameter instanceof ExpressionInterface) {
                $parameter = new SelectArgument($parameter);
            } elseif (! $parameter instanceof ArgumentInterface) {
                $parameter = new Value($parameter);
            }

            $this->parameters[] = $parameter;
        }

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $specification = $this->prepareSpecification();

        if ($this->parameters === []) {
            return str_replace('%%', '%', $specification);
        }

        $values = [];
        foreach ($this->parameters as $argument) {
            $values[] = $processor->renderArgument($argument, $paramPrefix, $paramIndex);
        }

        return vsprintf($specification, $values);
    }

    /**
     * Escape % signs and replace ? placeholders with %s, validating the count matches parameters.
     *
     * @throws Exception\RuntimeException
     */
    private function prepareSpecification(): string
    {
        $parametersCount = count($this->parameters);
        $specification   = str_replace('%', '%%', $this->expression);

        if ($parametersCount === 0) {
            return $specification;
        }

        $specification = str_replace(self::PLACEHOLDER, '%s', $specification, $count);

        // Fast path: placeholder count matches parameter count.
        // Slow path: check for named parameters (:name) used multiple times.
        if ($count !== $parametersCount) {
            preg_match_all('/:\w*/', $specification, $matches);
            if ($parametersCount !== count(array_unique($matches[0]))) {
                throw new Exception\RuntimeException(
                    'The number of replacements in the expression does not match the number of parameters'
                );
            }
        }

        return $specification;
    }
}
