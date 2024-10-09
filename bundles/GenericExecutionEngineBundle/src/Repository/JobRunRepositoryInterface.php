<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Repository;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Model\Element\ElementDescriptor;

interface JobRunRepositoryInterface
{
    public function createFromJob(Job $job, int $ownerId = null): JobRun;

    public function update(JobRun $jobRun): JobRun;

    public function updateLogLocalized(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en'
    ): void;

    /**
     * @throws Exception
     *
     * @internal
     */
    public function updateLogLocalizedWithDomain(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en',
        string $domain = 'admin'
    ): void;

    /**
     * @throws Exception
     */
    public function updateLog(JobRun $jobRun, string $message): void;

    public function getJobRunById(int $id, bool $forceReload = false, ?int $ownerId = null): JobRun;

    /**
     * @return JobRun[]
     */
    public function getJobRunsByUserId(
        int $ownerId = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0
    ): array;

    public function getTotalCount(): int;

    public function getRunningJobsByUserId(
        int $ownerId,
        array $orderBy = [],
        int $limit = 10,
    ): array;

    public function getLastJobRunByName(string $name): ?JobRun;

    /**
     * @param ElementDescriptor[] $selectedElements
     *
     * @throws JobNotFoundException
     */
    public function updateSelectedElements(JobRun $jobRun, array $selectedElements): void;
}
