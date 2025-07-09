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
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\Event;

class NotificationEmailEvent extends Event
{
    public function __construct(
        private readonly array $users,
        private readonly array $roles,
        private readonly WorkflowInterface $workflow,
        private readonly string $subjectType,
        private readonly ElementInterface $subject,
        private readonly string $action,
        private readonly string $mailType,
        private readonly string $mailPath,
        private array $recipients
    ) {
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getWorkflow(): WorkflowInterface
    {
        return $this->workflow;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function getSubject(): ElementInterface
    {
        return $this->subject;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMailType(): string
    {
        return $this->mailType;
    }

    public function getMailPath(): string
    {
        return $this->mailPath;
    }

    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
