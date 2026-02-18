<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Part\SqlProcessor;

use function vsprintf;

class Between extends AbstractExpression implements PredicateInterface
{
    protected string $operator = 'BETWEEN';

    protected ?ArgumentInterface $identifier = null;

    protected ?ArgumentInterface $minValue = null;

    protected ?ArgumentInterface $maxValue = null;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|int|float|string|ArgumentInterface $minValue = null,
        null|int|float|string|ArgumentInterface $maxValue = null
    ) {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }

        if ($minValue !== null) {
            $this->setMinValue($minValue);
        }

        if ($maxValue !== null) {
            $this->setMaxValue($maxValue);
        }
    }

    /**
     * Set identifier for comparison
     */
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : new Identifier($identifier);

        return $this;
    }

    /**
     * Get identifier for comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set minimum value for comparison
     */
    public function setMinValue(null|int|float|string|bool|ArgumentInterface $minValue): static
    {
        $this->minValue = $minValue instanceof ArgumentInterface
            ? $minValue
            : new Value($minValue);

        return $this;
    }

    /**
     * Get minimum value for comparison
     */
    public function getMinValue(): ?ArgumentInterface
    {
        return $this->minValue;
    }

    /**
     * Set maximum value for comparison
     */
    public function setMaxValue(null|int|float|string|bool|ArgumentInterface $maxValue): static
    {
        $this->maxValue = $maxValue instanceof ArgumentInterface
            ? $maxValue
            : new Value($maxValue);

        return $this;
    }

    /**
     * Get maximum value for comparison
     */
    public function getMaxValue(): ?ArgumentInterface
    {
        return $this->maxValue;
    }

    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new Exception\InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->minValue instanceof ArgumentInterface) {
            throw new Exception\InvalidArgumentException('minValue must be specified');
        }

        if (! $this->maxValue instanceof ArgumentInterface) {
            throw new Exception\InvalidArgumentException('maxValue must be specified');
        }

        $id  = $processor->renderArgument($this->identifier, $paramPrefix, $paramIndex);
        $min = $processor->renderArgument($this->minValue, $paramPrefix, $paramIndex);
        $max = $processor->renderArgument($this->maxValue, $paramPrefix, $paramIndex);

        if ($this->specification !== null) {
            return vsprintf($this->specification, [$id, $min, $max]);
        }

        return "{$id} {$this->operator} {$min} AND {$max}";
    }
}
