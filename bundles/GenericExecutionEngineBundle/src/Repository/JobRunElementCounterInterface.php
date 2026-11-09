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

/**
 * Counts the processed elements of the current step of a job run.
 *
 * @internal
 */
interface JobRunElementCounterInterface
{
    /**
     * Atomically increments the processed elements counter of the current job step
     * and returns the new value. Safe to call from several workers processing the
     * same job run in parallel.
     */
    public function incrementProcessedElementsForStep(JobRun $jobRun): int;
}
