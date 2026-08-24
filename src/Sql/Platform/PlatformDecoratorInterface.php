<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

/**
 * @api
 */
interface PlatformDecoratorInterface extends SqlInterface, PreparableSqlInterface
{
    public function setSubject(
        SqlInterface|PreparableSqlInterface $subject,
    ): self;
}
