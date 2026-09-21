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

namespace Pimcore\Asset\StorageQueue;

/**
 * @internal
 */
final readonly class StorageQueueProcessingResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        private int $processedRows,
        private int $failedRows,
        private int $pendingRows,
        private bool $timedOut,
        private array $errors,
    ) {
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    public function getPendingRows(): int
    {
        return $this->pendingRows;
    }

    public function isTimedOut(): bool
    {
        return $this->timedOut;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
