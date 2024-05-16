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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Security;

use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Model\UserInterface;
use Pimcore\Tool\Authentication;

/**
 * @internal
 */
final class PermissionService implements PermissionServiceInterface
{
    private ?UserInterface $user;

    public function __construct(
    ) {
        $this->user = Authentication::authenticateSession();
    }

    public function allowedToSeeJobRuns(): void
    {
        if (!$this->isAllowedToSeeJobRuns()) {
            throw new PermissionException('You are not allowed to see job run.');
        }
    }

    public function allowedToSeeAllJobRuns(): void
    {
        if (!$this->isAllowedToSeeAllJobRuns()) {
            throw new PermissionException(
                'You are not allowed to see all job runs. You can just see your own job runs.'
            );
        }
    }

    public function isAllowedToSeeJobRuns(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->isAllowed(PermissionConstants::GEE_JOB_RUN);
    }

    public function isAllowedToSeeAllJobRuns(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS);
    }
}
