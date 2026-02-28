<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

abstract class AbstractPlatform
{
    /** @var array<class-string, SqlDecoratorInterface> */
    protected array $decorators = [];

    public function setTypeDecorator(string $type, SqlDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }

    public function getTypeDecorator(object $subject): ?SqlDecoratorInterface
    {
        $subjectClass = $subject::class;
        if (isset($this->decorators[$subjectClass])) {
            return $this->decorators[$subjectClass];
        }

        foreach ($this->decorators as $type => $decorator) {
            if ($subject instanceof $type) {
                return $decorator;
            }
        }

        return null;
    }
}
