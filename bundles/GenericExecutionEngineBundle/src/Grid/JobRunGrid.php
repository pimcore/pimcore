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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Grid;

use DateTimeInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;
use Pimcore\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class JobRunGrid implements JobRunGridInterface
{
    public function __construct(
        private readonly CurrentMessageProviderInterface $currentMessageProvider,
        private readonly ExecutionContextInterface $executionContext,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function convertJobRunToArray(JobRun $jobRun): array
    {
        $currentMessage = $this->currentMessageProvider->getMessageFromSerializedString(
            $jobRun->getCurrentMessage()
        );

        $domain = $this->executionContext->getTranslationDomain($jobRun->getExecutionContext());

        return [
            'id' => $jobRun->getId(),
            'job_name' => $this->translator->trans($jobRun->getJob()?->getName(), [], $domain),
            'started_at' => $jobRun->getCreationDate(),
            'last_update_at' => $jobRun->getModificationDate(),
            'owner' => $jobRun->getOwnerId() ? User::getById($jobRun->getOwnerId())?->getName() : null,
            'state' => $jobRun->getState()->value,
            'current_step' => $jobRun->getCurrentStep(),
            'current_message' => $currentMessage->getMessage(),
            'canCancel' => $jobRun->getState() === JobRunStates::RUNNING,
            'log' => array_map(
                static fn (LogLine $line) =>
                [
                    'logMessage' => $line->getLogLine(),
                    'createdAt' => $line->getCreatedAt()->format(DateTimeInterface::ATOM),
                ],
                $jobRun->getLogs()
            ),
        ];
    }
}
