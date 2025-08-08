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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class JobRunStatusChangeSubscriber
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly JobRunRepositoryInterface $jobRunRepository
    ) {

    }

    private const STATE_FIELD = 'state';

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof JobRun) {
            return;
        }

        if ($args->hasChangedField(self::STATE_FIELD)) {
            $oldStatus = $args->getOldValue(self::STATE_FIELD);
            $newStatus = $args->getNewValue(self::STATE_FIELD);

            if ($oldStatus !== $newStatus) {
                $jobRun = $this->jobRunRepository->getJobRunById($entity->getId());
                $jobName = $jobRun->getJob()?->getName();
                $event = new JobRunStateChangedEvent(
                    jobRunId: $entity->getId(),
                    jobName: $jobName,
                    jobRunOwnerId: $jobRun->getOwnerId(),
                    oldState: $oldStatus,
                    newState: $newStatus
                );
                $this->eventDispatcher->dispatch($event);
            }
        }
    }
}
