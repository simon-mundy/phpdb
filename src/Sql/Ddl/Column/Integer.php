<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

use function is_int;
use function is_string;

class Integer extends Column
{
    /**
     * @inheritDoc
     *
     * @mago-expect analysis:mixed-assignment
     */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();
        $options        = $this->getOptions();

        $length = $options['length'] ?? null;
        if (is_int($length) || is_string($length)) {
            $expressionData['spec'] .= " ({$length})";
        }

        return $expressionData;
    }
}
