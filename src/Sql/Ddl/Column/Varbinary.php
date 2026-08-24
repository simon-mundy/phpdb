<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Varbinary extends AbstractLengthColumn
{
    protected string $type = 'VARBINARY';
}
