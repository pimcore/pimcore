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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants;

/**
 * @internal
 */
class PermissionConstants
{
    /**
     * Permission have a max length of 50 chars!
     */
    public const GEE_JOB_RUN = 'gee_job_run_permission';

    public const GEE_SEE_ALL_JOB_RUNS = 'gee_see_all_job_runs_permission';
}
