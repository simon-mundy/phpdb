<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Platform\Sql92Renderer;
use PhpDb\Sql\Platform\SqlDecoratorInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    public function testGetTypeDecoratorReturnsNullWhenEmpty(): void
    {
        $platform = new Sql92Renderer();

        self::assertNull($platform->getTypeDecorator(new Select()));
    }

    public function testGetTypeDecoratorExactClassMatch(): void
    {
        $platform  = new Sql92Renderer();
        $decorator = new class implements SqlDecoratorInterface {
            public function prepare(object $subject, AbstractSqlRenderer $renderer): void
            {
            }
        };

        $platform->setTypeDecorator(Select::class, $decorator);

        self::assertSame($decorator, $platform->getTypeDecorator(new Select()));
        self::assertNull($platform->getTypeDecorator(new Update()));
    }

    public function testGetTypeDecoratorInstanceofFallback(): void
    {
        $platform  = new Sql92Renderer();
        $decorator = new class implements SqlDecoratorInterface {
            public function prepare(object $subject, AbstractSqlRenderer $renderer): void
            {
            }
        };

        $platform->setTypeDecorator(Select::class, $decorator);

        $subclass = new class extends Select {
        };

        self::assertSame($decorator, $platform->getTypeDecorator($subclass));
    }
}
