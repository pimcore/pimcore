<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation;

/**
 * Reusable format validation methods for env var definitions.
 *
 * Collects validation errors into an array rather than throwing exceptions,
 * matching the validate() return-type convention of EnvVarDefinitionInterface.
 *
 * All methods standardize on parse_url() for URL validation (not filter_var).
 *
 * @internal
 */
final class FormatValidator
{
    /** @var list<string> */
    private array $errors = [];

    public function requireNonEmpty(string $value, string $label): self
    {
        if ($value === '') {
            $this->errors[] = sprintf('%s is required and cannot be empty.', $label);
        }

        return $this;
    }

    /**
     * Only adds an error if the value is non-empty (use requireNonEmpty first
     * if the field is required).
     */
    public function requireValidUrl(string $value, string $label): self
    {
        if ($value === '') {
            return $this;
        }

        $parsed = parse_url($value);
        if ($parsed === false || !isset($parsed['host'])) {
            $this->errors[] = sprintf('Invalid %s URL: "%s".', $label, $value);
        }

        return $this;
    }

    /**
     * @param list<string> $allowedSchemes e.g. ['amqp', 'amqps']
     */
    public function requireUrlWithScheme(
        string $value,
        string $label,
        array $allowedSchemes,
    ): self {
        if ($value === '') {
            return $this;
        }

        $parsed = parse_url($value);
        if ($parsed === false || !isset($parsed['host'])) {
            $this->errors[] = sprintf('Invalid %s URL: "%s".', $label, $value);

            return $this;
        }

        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, $allowedSchemes, true)) {
            $this->errors[] = sprintf(
                '%s URL must use %s scheme, got "%s://".',
                $label,
                implode(' or ', array_map(
                    static fn (string $s): string => $s . '://',
                    $allowedSchemes,
                )),
                $scheme,
            );
        }

        return $this;
    }

    public function requirePortInRange(int $port, string $label): self
    {
        if ($port < 1 || $port > 65535) {
            $this->errors[] = sprintf(
                '%s must be between 1 and 65535, got %d.',
                $label,
                $port,
            );
        }

        return $this;
    }

    /**
     * Only adds an error if the value is non-empty (use requireNonEmpty first
     * if the field is required).
     */
    public function requireMinLength(string $value, string $label, int $minLength): self
    {
        if ($value === '') {
            return $this;
        }

        if (strlen($value) < $minLength) {
            $this->errors[] = sprintf(
                '%s must be at least %d characters.',
                $label,
                $minLength,
            );
        }

        return $this;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Return all collected errors and reset the internal state.
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        $errors = $this->errors;
        $this->errors = [];

        return $errors;
    }
}
