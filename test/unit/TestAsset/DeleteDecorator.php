<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class DeleteDecorator extends Sql\Delete implements Sql\Strategy\TypeDecoratorInterface
{
    public Sql\SqlInterface|Sql\PreparableSqlInterface|null $subject;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(
        Sql\SqlInterface|Sql\PreparableSqlInterface|null $subject
    ): DeleteDecorator {
        $this->subject = $subject;
        return $this;
    }
}
