<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature\TestAsset;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\SequenceCapableInterface;

/**
 * Stub interface combining PlatformInterface and SequenceCapableInterface for mocking
 */
interface SequenceCapablePlatformInterface extends PlatformInterface, SequenceCapableInterface
{
}
