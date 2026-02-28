<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Part\SqlProcessor;

class Column implements ColumnInterface
{
    protected string|int|float|bool|Literal|Value|null $default;

    protected bool $isNullable = false;

    protected string $name = '';

    protected array $options = [];

    /** @var ConstraintInterface[] */
    protected array $constraints = [];

    protected string $specification = '%s %s';

    protected string $type = 'INTEGER';

    public function __construct(
        string $name = '',
        bool $nullable = false,
        mixed $default = null,
        array $options = []
    ) {
        $this->setName($name);
        $this->setNullable($nullable);
        $this->setDefault($default);
        $this->setOptions($options);
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    public function setNullable(bool $nullable): static
    {
        $this->isNullable = $nullable;
        return $this;
    }

    #[Override]
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function setDefault(string|int|float|bool|Literal|Value|null $default): static
    {
        $this->default = $default;
        return $this;
    }

    #[Override]
    public function getDefault(): string|int|float|bool|Literal|Value|null
    {
        return $this->default;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function setOption(string $name, bool|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    #[Override]
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addConstraint(ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $sql = $processor->renderArgument(new Identifier($this->name), $paramPrefix, $paramIndex)
            . ' ' . $this->type;

        $sql .= $this->renderTypeModifier();

        if ($this->isNullable === false) {
            $sql .= ' NOT NULL';
        }

        if ($this->default !== null) {
            $defaultArg = $this->default instanceof ArgumentInterface
                ? $this->default
                : new Value($this->default);
            $sql       .= ' DEFAULT ' . $processor->renderArgument($defaultArg, $paramPrefix, $paramIndex);
        }

        foreach ($this->constraints as $constraint) {
            $sql .= ' ' . $constraint->renderSql($processor, $paramPrefix, $paramIndex);
        }

        return $sql;
    }

    /**
     * Hook for subclasses to append type modifiers (e.g. length, precision).
     */
    protected function renderTypeModifier(): string
    {
        return '';
    }
}
