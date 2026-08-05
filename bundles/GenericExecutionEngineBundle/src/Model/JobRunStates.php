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
