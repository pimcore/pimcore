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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * @internal
 */
final class StepConditionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JobExecutionAgentInterface $jobExecutionAgent,
        private readonly JobRunExtractorInterface $jobRunExtractor,
        private readonly LoggerInterface $genericExecutionEngineLogger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof GenericExecutionEngineMessageInterface) {
            if ($this->jobRunExtractor->checkCondition($message)) {
                return $stack->next()->handle($envelope, $stack);
            }

            $this->logToJobRun(
                $message,
            );

            $this->jobExecutionAgent->continueJobMessageExecution($message);

            return $envelope;
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function logToJobRun(
        GenericExecutionEngineMessageInterface $message
    ): void {
        $jobRun = $this->jobRunExtractor->getJobRun($message);
        $jobName = $jobRun->getJob()?->getName();
        $stepId = $message->getCurrentJobStep();
        $stepName = $jobRun->getJob()?->getSteps()[$stepId]->getName();
        $params['%jobName%'] = $jobName;
        $params['%stepId%'] = $stepId;
        $params['%stepName%'] = $stepName;

        $this->jobRunExtractor->logMessageToJobRun(
            $jobRun,
            'gee_middleware_step_condition_not_met',
            $params
        );

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:
            Skipping step $stepName with id $stepId of Job '$jobName', job condition not met."
        );
    }
}
