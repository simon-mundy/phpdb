<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class UpdateDecorator extends Sql\Update implements Sql\Strategy\TypeDecoratorInterface
{
    public Sql\SqlInterface|Sql\PreparableSqlInterface|null $subject;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(
        Sql\SqlInterface|Sql\PreparableSqlInterface|null $subject
    ): UpdateDecorator {
        $this->subject = $subject;
        return $this;
    }
}
