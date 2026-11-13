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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums;

/**
 * Keys the engine itself stores in a job run's `context`. User and handler keys must not use them.
 *
 * @internal
 */
enum JobRunContextKey: string
{
    /**
     * The version coauthor (`type` and `coauthor`) that was active when the job started.
     */
    case COAUTHOR = '_coauthor';
}
