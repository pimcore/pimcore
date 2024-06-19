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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Model;

enum JobRunStates: string
{
    case RUNNING = 'running';
    case FINISHED = 'finished';
    case FINISHED_WITH_ERRORS = 'finished_with_errors';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case NOT_STARTED = 'not_started';
}
