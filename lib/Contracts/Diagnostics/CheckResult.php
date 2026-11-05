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
