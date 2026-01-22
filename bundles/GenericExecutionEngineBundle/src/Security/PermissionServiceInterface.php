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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Security;

use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;

/**
 * @internal
 */
interface PermissionServiceInterface
{
    /**
     * @throws PermissionException
     */
    public function allowedToSeeJobRuns(): void;

    /**
     * @throws PermissionException
     */
    public function allowedToSeeAllJobRuns(): void;

    public function isAllowedToSeeJobRuns(): bool;

    public function isAllowedToSeeAllJobRuns(): bool;
}
