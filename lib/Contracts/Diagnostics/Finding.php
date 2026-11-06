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
 * One observation of a check. The message is written by the check author as a fixed text and must never
 * embed values read from the instance (credentials, queries, personal data).
 */
final readonly class Finding
{
    public function __construct(
        public string $code,
        public Severity $severity,
        public string $message,
        public ?string $subject = null,
    ) {
        if ($code === '') {
            throw new InvalidArgumentException('"code" must be a non-empty string.');
        }

        if ($message === '') {
            throw new InvalidArgumentException('"message" must be a non-empty string.');
        }

        if ($subject === '') {
            throw new InvalidArgumentException('"subject" must be null or a non-empty string.');
        }
    }

    /**
     * @return array{code: string, severity: string, message: string, subject?: string}
     */
    public function toArray(): array
    {
        $data = ['code' => $this->code, 'severity' => $this->severity->value, 'message' => $this->message];
        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }

        return $data;
    }
}
