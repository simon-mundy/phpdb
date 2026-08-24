<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ExpressionInterface;

use function implode;
use function is_string;
use function str_replace;

/**
 * @api
 */
class Check extends AbstractConstraint
{
    protected string|ExpressionInterface $expression;

    protected string $specification = 'CHECK (%s)';

    public function __construct(string|ExpressionInterface $expression, ?string $name)
    {
        parent::__construct(null, $name);

        $this->expression = $expression;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $specParts = [];
        $values    = [];

        if ('' !== $this->name) {
            $specParts[] = $this->namedSpecification;
            $values[]    = new Identifier($this->name);
        }

        if ($this->expression instanceof ExpressionInterface) {
            $expressionData = $this->expression->getExpressionData();
            $specParts[]    = str_replace('%s', $expressionData['spec'], $this->specification);
            $values         = [...$values, ...$expressionData['values']];
        }

        if (is_string($this->expression) && '' !== $this->expression) {
            $specParts[] = $this->specification;
            $values[]    = new Literal($this->expression);
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $values,
        ];
    }
}
