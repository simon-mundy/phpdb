<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use PhpDb\Sql\Platform\AbstractSqlRenderer;

interface PlatformInterface
{
    /**
     * Get name
     */
    public function getName(): string;

    /**
     * Get Sql platform decorator registry
     */
    public function getSqlPlatformDecorator(): AbstractSqlRenderer;

    /**
     * Get quote identifier symbol
     */
    public function getQuoteIdentifierSymbol(): string;

    /**
     * Quote identifier — dotted names (e.g. "table.column") are auto-split.
     * Pass $prefix explicitly for schema-qualified tables.
     */
    public function quoteIdentifier(string $name, ?string $prefix = null): string;

    /**
     * Quote identifier chain
     *
     * @param string|string[] $identifierChain
     */
    public function quoteIdentifierChain(array|string $identifierChain): string;

    /**
     * Get quote value symbol
     */
    public function getQuoteValueSymbol(): string;

    /**
     * Quote value
     *
     * Will throw a notice when used in a workflow that can be considered "unsafe"
     */
    public function quoteValue(string $value): string;

    /**
     * Quote Trusted Value
     *
     * The ability to quote values without notices
     */
    public function quoteTrustedValue(int|float|string|bool $value): ?string;

    /**
     * Quote value list
     *
     * @param string|string[] $valueList
     */
    public function quoteValueList(array|string $valueList): string;

    /**
     * Get identifier separator
     */
    public function getIdentifierSeparator(): string;
}
