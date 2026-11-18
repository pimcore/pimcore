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

namespace Pimcore\Contracts\Diagnostics;

use InvalidArgumentException;
use function array_is_list;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;

final readonly class CheckResult
{
    /**
     * @param list<Finding> $findings
     * @param array<string, bool|int|float|string|null> $metrics
     * @param list<NotAvailable> $notAvailable
     */
    public function __construct(
        public array $findings = [],
        public array $metrics = [],
        public array $notAvailable = [],
    ) {
        if (!array_is_list($findings)) {
            throw new InvalidArgumentException('"findings" must be a list.');
        }

        if (!array_is_list($notAvailable)) {
            throw new InvalidArgumentException('"notAvailable" must be a list.');
        }

        foreach ($findings as $finding) {
            if (!$finding instanceof Finding) {
                throw new InvalidArgumentException('Every entry of "findings" must be a Finding.');
            }
        }

        foreach ($notAvailable as $item) {
            if (!$item instanceof NotAvailable) {
                throw new InvalidArgumentException('Every entry of "notAvailable" must be a NotAvailable.');
            }
        }

        foreach ($metrics as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Every key of "metrics" must be a string.');
            }

            if ($value !== null && !is_bool($value) && !is_int($value) && !is_float($value) && !is_string($value)) {
                throw new InvalidArgumentException('Every value of "metrics" must be a scalar or null.');
            }

            if (is_float($value) && !is_finite($value)) {
                throw new InvalidArgumentException('Every float of "metrics" must be finite.');
            }
        }
    }

    /**
     * @return array{
     *     findings: list<array{code: string, severity: string, message: string, subject?: string}>,
     *     metrics: array<string, bool|int|float|string|null>,
     *     not_available: list<array{subject: string, reason: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'findings' => array_map(static fn (Finding $finding): array => $finding->toArray(), $this->findings),
            'metrics' => $this->metrics,
            'not_available' => array_map(
                static fn (NotAvailable $item): array => $item->toArray(),
                $this->notAvailable,
            ),
        ];
    }
}
