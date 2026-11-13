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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\JobRunContextKey;
use Pimcore\Model\Version\CoauthorContextInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use function is_array;
use function is_string;

/**
 * Carries the version coauthor of the call that started a job into the worker: when the job run stores one
 * (see `JobExecutionAgent::startJobExecution()`), it is set on the coauthor context for the step and the
 * previous state is restored afterwards, also when the step fails. A job run without a stored coauthor is
 * handled unchanged.
 *
 * @internal
 */
final class CoauthorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JobRunExtractorInterface $jobRunExtractor,
        private readonly CoauthorContextInterface $coauthorContext,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if (!$message instanceof GenericExecutionEngineMessageInterface) {
            return $stack->next()->handle($envelope, $stack);
        }

        $coauthor = $this->jobRunExtractor->getJobRun($message)->getContext()[JobRunContextKey::COAUTHOR->value] ?? null;
        if (
            !is_array($coauthor)
            || !is_string($coauthor['type'] ?? null)
            || !is_string($coauthor['coauthor'] ?? null)
        ) {
            return $stack->next()->handle($envelope, $stack);
        }

        return $this->coauthorContext->withCoauthor(
            $coauthor['type'],
            $coauthor['coauthor'],
            fn (): Envelope => $stack->next()->handle($envelope, $stack)
        );
    }
}
