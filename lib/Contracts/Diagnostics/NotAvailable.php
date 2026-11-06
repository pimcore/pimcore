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

/**
 * Something a check could not determine, e.g. because the database user lacks a privilege.
 */
final readonly class NotAvailable
{
    public function __construct(
        public string $subject,
        public string $reason,
    ) {
        if ($subject === '') {
            throw new InvalidArgumentException('"subject" must be a non-empty string.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('"reason" must be a non-empty string.');
        }
    }

    /**
     * @return array{subject: string, reason: string}
     */
    public function toArray(): array
    {
        return ['subject' => $this->subject, 'reason' => $this->reason];
    }
}
