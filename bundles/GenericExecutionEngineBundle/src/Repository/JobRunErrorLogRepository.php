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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;

final class JobRunErrorLogRepository implements JobRunErrorLogRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $pimcoreEntityManager,
    ) {
    }

    public function createFromJobRun(
        JobRun $jobRun,
        ?int $elementId = null,
        ?string $message = null
    ): void {
        $jobRunErrorLog = new JobRunErrorLog(
            $jobRun->getId(),
            $jobRun->getCurrentStep(),
            $elementId,
            $message
        );

        $this->pimcoreEntityManager->persist($jobRunErrorLog);
        $this->pimcoreEntityManager->flush();
    }

    public function update(JobRunErrorLog $jobRunErrorLog): void
    {
        $this->pimcoreEntityManager->persist($jobRunErrorLog);
        $this->pimcoreEntityManager->flush();
    }

    /**
     * @return JobRunErrorLog[]
     */
    public function getLogsByJobRunId(
        int $jobRunId,
        ?int $step = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $criteria = ['jobRunId' => $jobRunId];
        if ($step !== null && $step >= 0) {
            $criteria['step'] = $step;
        }

        return $this->getLogRepository()->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getTotalCount(): int
    {
        return $this->getLogRepository()->count([]);
    }

    public function getTotalCountByJobRunId(int $jobRunId): int
    {
        return $this->getLogRepository()->count(['jobRunId' => $jobRunId]);
    }

    private function getLogRepository(): EntityRepository
    {
        return $this->pimcoreEntityManager->getRepository(JobRunErrorLog::class);
    }
}
