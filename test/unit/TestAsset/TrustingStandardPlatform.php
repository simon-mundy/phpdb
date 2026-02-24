<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\Adapter\Platform\Standard;

final class TrustingStandardPlatform extends Standard
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
