<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Part\SqlProcessor;

/**
 * @see doc section http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
 */
abstract class AbstractTimestampColumn extends Column
{
    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $sql     = parent::toSql($processor, $paramPrefix, $paramIndex);
        $options = $this->getOptions();

        if (isset($options['on_update'])) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }

        return $sql;
    }
}
