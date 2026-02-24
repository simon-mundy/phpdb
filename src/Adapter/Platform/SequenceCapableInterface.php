<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

interface SequenceCapableInterface
{
    public function getNextSequenceValueSql(string $sequenceName): string;

    public function getCurrentSequenceValueSql(string $sequenceName): string;
}
