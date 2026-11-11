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
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Model\UserInterface;
use Pimcore\Tool\Authentication;

/**
 * @internal
 */
final class PermissionService implements PermissionServiceInterface
{
    private ?UserInterface $user = null;

    private bool $userResolved = false;

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
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        return $user->isAllowed(PermissionConstants::GEE_JOB_RUN);
    }

    public function isAllowedToSeeAllJobRuns(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        return $user->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS);
    }

    /**
     * Resolved on first use, not in the constructor: the service is built as a dependency of
     * JobRunStatusChangeSubscriber, which Doctrine instantiates on the first preUpdate of any
     * entity. Reading the session there starts it, which fails once a streamed response has
     * sent its headers and closes the EntityManager in the middle of flush().
     */
    private function getUser(): ?UserInterface
    {
        if (!$this->userResolved) {
            $this->user = Authentication::authenticateSession();
            $this->userResolved = true;
        }

        return $this->user;
    }
}
