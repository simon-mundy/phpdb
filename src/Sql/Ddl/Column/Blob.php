<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Blob extends AbstractLengthColumn
{
    protected string $specification = '%s %s';

    /** @var string Change type to blob */
    protected string $type = 'BLOB';
}
