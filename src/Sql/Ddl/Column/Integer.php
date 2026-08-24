<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

/**
 * @api
 */
class Integer extends Column
{
    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        if (isset($options['length'])) {
            $expressionData['spec'] .= " ({$options['length']})";
        }

        return $expressionData;
    }
}
