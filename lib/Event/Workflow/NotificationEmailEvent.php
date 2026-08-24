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

namespace Pimcore\Event\Workflow;

use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before a workflow transition notification is sent, so that listeners can adjust the
 * recipients configured on the transition - for example to add the element's owner, or to resolve a
 * role to the users that currently hold it.
 */
class NotificationEmailEvent extends Event
{
    /**
     * @param string[] $users names of the users to notify
     * @param string[] $roles names of the roles to notify
     */
    public function __construct(
        private readonly Transition $transition,
        private readonly WorkflowInterface $workflow,
        private readonly ElementInterface $subject,
        private readonly string $mailType,
        private readonly string $mailPath,
        private array $users,
        private array $roles,
    ) {
    }

    /**
     * @return string[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param string[] $users
     */
    public function setUsers(array $users): void
    {
        $this->users = $users;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getWorkflow(): WorkflowInterface
    {
        return $this->workflow;
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }

    public function getSubject(): ElementInterface
    {
        return $this->subject;
    }

    public function getMailType(): string
    {
        return $this->mailType;
    }

    public function getMailPath(): string
    {
        return $this->mailPath;
    }
}
