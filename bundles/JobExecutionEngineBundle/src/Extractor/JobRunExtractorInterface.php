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

namespace Pimcore\Bundle\JobExecutionEngineBundle\Extractor;

use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\JobStepInterface;

interface JobRunExtractorInterface
{
    public function getElementsToProcess(JobRun $jobRun, string $type): array;

    public function getJobRun(JobExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun;

    public function getJobStep(JobExecutionEngineMessageInterface $message): JobStepInterface;

    public function getEnvironmentData(JobRun $jobRun): array;

    public function checkCondition(JobExecutionEngineMessageInterface $message): bool;

    public function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void;
}
