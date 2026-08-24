<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;

use function array_splice;

/**
 * @api
 */
abstract class AbstractLengthColumn extends Column
{
    protected string $specification = '%s %s(%s)';

    protected ?int $length = null;

    /** @param array<string, mixed> $options */
    public function __construct(
        string $name,
        ?int $length = null,
        bool $nullable = false,
        string|int|float|bool|Literal|Value|null $default = null,
        array $options = [],
    ) {
        $this->setLength($length);

        parent::__construct($name, $nullable, $default, $options);
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();

        if ($this->getLengthExpression() !== '' && $this->getLengthExpression() !== '0') {
            array_splice($expressionData['values'], offset: 2, length: 0, replacement: [new Literal(
                $this->getLengthExpression(),
            )]);
        }

        return $expressionData;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length = 0): static
    {
        $this->length = $length;

        return $this;
    }

    protected function getLengthExpression(): string
    {
        return (string) $this->length;
    }
}
