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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Extractor;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Model\Element\ElementInterface;

interface JobRunExtractorInterface
{
    public const ASSET_TYPE = 'asset';

    public const DOCUMENT_TYPE = 'document';

    public const FOLDER_TYPE = 'folder';

    public const OBJECT_TYPE = 'object';

    public function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun;

    public function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface;

    public function getEnvironmentData(JobRun $jobRun): array;

    public function checkCondition(GenericExecutionEngineMessageInterface $message): bool;

    public function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void;

    public function getElementFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): ?ElementInterface;

    /** @return ElementInterface[]  */
    public function getElementsFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): array;
}
