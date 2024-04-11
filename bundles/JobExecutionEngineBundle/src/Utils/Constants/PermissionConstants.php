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

namespace Pimcore\Bundle\JobExecutionEngineBundle\Utils\Constants;

/**
 * @internal
 */
class PermissionConstants
{
    /**
     * Permission have a max length of 50 chars!
     */
    public const PJEE_JOB_RUN = 'pjee_job_run_permission';

    public const PJEE_SEE_ALL_JOB_RUNS = 'pjee_see_all_job_runs_permission';
}
