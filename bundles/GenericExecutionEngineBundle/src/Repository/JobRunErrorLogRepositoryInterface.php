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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Repository;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;

interface JobRunErrorLogRepositoryInterface
{
    public function createFromJobRun(
        JobRun $jobRun,
        ?int $elementId = null,
        ?string $message = null
    ): void;

    public function update(JobRunErrorLog $jobRunErrorLog): void;

    /**
     * @return JobRunErrorLog[]
     */
    public function getLogsByJobRunId(
        int $jobRunId,
        ?int $step = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0
    ): array;

    public function getTotalCount(): int;

    public function getTotalCountByJobRunId(int $jobRunId): int;
}
