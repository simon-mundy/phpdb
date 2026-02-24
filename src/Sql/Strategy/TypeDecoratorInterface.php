<?php

declare(strict_types=1);

namespace PhpDb\Sql\Strategy;

use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

interface TypeDecoratorInterface
{
    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject
    ): TypeDecoratorInterface;
}
