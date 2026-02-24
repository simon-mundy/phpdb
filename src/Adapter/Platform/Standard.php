<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;

use function addcslashes;

class Standard extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        if (! isset($this->driver)) {
            throw VunerablePlatformQuoteException::forPlatformAndMethod(
                static::class,
                __METHOD__
            );
        }
        return '\'' . addcslashes($value, "\x00\n\r\\'\"\x1a") . '\'';
    }
}
