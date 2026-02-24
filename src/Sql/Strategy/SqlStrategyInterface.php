<?php

declare(strict_types=1);

namespace PhpDb\Sql\Strategy;

use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

interface SqlStrategyInterface extends TypeDecoratorInterface, PreparableSqlInterface, SqlInterface
{
}
