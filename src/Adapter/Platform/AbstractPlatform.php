<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PDO;
use PhpDb\Adapter\Driver;
use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;

use function addcslashes;
use function array_map;
use function explode;
use function implode;
use function str_contains;
use function str_replace;

/**
 * @property Driver\DriverInterface|Driver\PdoDriverInterface|PDO $driver
 */
abstract class AbstractPlatform implements PlatformInterface
{
    /** @var string[] */
    protected array $quoteIdentifier = ['"', '"'];

    protected string $quoteIdentifierTo = '\'';

    protected string $identifierSeparator = '.';

    protected bool $quoteIdentifiers = true;

    /** @var array<string, string> */
    private array $identifierCache = [];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifier(string $name, ?string $prefix = null): string
    {
        if ($prefix === null && str_contains($name, '.')) {
            [$prefix, $name] = explode('.', $name, 2);
        }

        if (! $this->quoteIdentifiers) {
            return $prefix !== null
                ? $prefix . $this->identifierSeparator . $name
                : $name;
        }

        if ($prefix !== null) {
            $key                                  = $prefix . '.' . $name;
            return $this->identifierCache[$key]
                ??= $this->quoteIdentifier[0]
                    . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $prefix)
                    . $this->quoteIdentifier[1]
                    . $this->identifierSeparator
                    . $this->quoteIdentifier[0]
                    . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $name)
                    . $this->quoteIdentifier[1];
        }

        return $this->identifierCache[$name]
            ??= $this->quoteIdentifier[0]
                . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $name)
                . $this->quoteIdentifier[1];
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierChain(array|string $identifierChain): string
    {
        return '"' . implode('"."', (array) str_replace('"', '\\"', $identifierChain)) . '"';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getQuoteIdentifierSymbol(): string
    {
        return $this->quoteIdentifier[0];
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getQuoteValueSymbol(): string
    {
        return '\'';
    }

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

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteTrustedValue(int|float|string|bool $value): ?string
    {
        return '\'' . addcslashes((string) $value, "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValueList(array|string $valueList): string
    {
        return implode(', ', array_map([$this, 'quoteValue'], (array) $valueList));
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getIdentifierSeparator(): string
    {
        return $this->identifierSeparator;
    }
}
