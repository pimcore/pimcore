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

final class TableConstants
{
    public const USER_PERMISSION_DEF_TABLE = 'users_permission_definitions';

    public const JOB_RUN_TABLE = 'generic_execution_engine_job_run';

    public const ERROR_LOG_TABLE = 'generic_execution_engine_error_log';
}
