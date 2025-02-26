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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Extractor;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Helper\SymfonyExpression\ExpressionServiceInterface;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;

final class JobRunExtractor implements JobRunExtractorInterface
{
    public function __construct(
        private readonly ExpressionServiceInterface $symfonyExpressionService,
        private readonly JobRunRepositoryInterface $jobRunRepository
    ) {
    }

    public function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunRepository->getJobRunById($message->getJobRunId(), $forceReload);
    }

    public function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface
    {
        $jobRun = $this->getJobRun($message);
        $jobSteps = $jobRun->getJob()?->getSteps();

        if (!array_key_exists($message->getCurrentJobStep(), $jobSteps)) {
            throw new NotFoundException('Job not found!');
        }

        return $jobSteps[$message->getCurrentJobStep()];
    }

    public function getEnvironmentData(JobRun $jobRun): array
    {
        if (!$jobRun->getJob()) {
            return [];
        }

        return $jobRun->getJob()->getEnvironmentData();
    }

    /**
     * Logs a translation key to the job run which can be viewed in the job run overview
     *
     * @throws Exception
     */
    public function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void {
        $this->jobRunRepository->updateLogLocalized(
            $jobRun,
            $translationKey,
            $params
        );
    }

    public function checkCondition(GenericExecutionEngineMessageInterface $message): bool
    {
        $jobRun = $this->getJobRun($message);
        $jobRunContext = $jobRun->getContext();
        $currentStep = $this->getJobStep($message);
        $currentStepCondition = $currentStep->getCondition();
        $contentVariables = $this->extractContentVariables($jobRunContext, $this->getEnvironmentData($jobRun));

        if ($currentStepCondition === '') {
            return true;
        }

        return $this->symfonyExpressionService->evaluate($currentStepCondition, $contentVariables);
    }

    private function extractContentVariables(
        ?array $jobRunContext = null,
        ?array $environmentData = null
    ): array {
        $variables = [];
        if (!empty($jobRunContext)) {
            $variables['context'] = $jobRunContext;
        }
        if (!empty($environmentData)) {
            $variables['environmentData'] = $environmentData;
        }

        return $variables;
    }

    public function getElementFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): ?ElementInterface {
        $elementDescriptor = $message->getElement();
        if (!$elementDescriptor) {
            return null;
        }

        $element = $this->getElementByType(
            $elementDescriptor->getType(),
            $elementDescriptor->getId(),
            $types
        );

        if (!$element) {
            return null;
        }

        return $element;
    }

    public function getElementsFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::ASSET_TYPE]
    ): array {

        $elementsToProcess = [];
        $jobRun = $this->getJobRun($message);

        /** @var ElementDescriptor[] $elementDescriptors */
        $elementDescriptors = $jobRun->getJob()?->getSelectedElements();

        foreach ($elementDescriptors as $elementDescriptor) {
            $element = $this->getElementByType(
                $elementDescriptor->getType(),
                $elementDescriptor->getId(),
                $types
            );
            if ($element !== null) {
                $elementsToProcess[] = $element;
            }
        }

        return $elementsToProcess;
    }

    private function getElement(string $type, int $id): ?ElementInterface
    {
        $element = Service::getElementById($type, $id);

        if (!$element || $element->getType() === JobRunExtractorInterface::FOLDER_TYPE) {
            return null;
        }

        return $element;
    }

    private function getElementByType(
        string $elementType,
        int $elementId,
        array $typesToLookFor = [JobRunExtractorInterface::ASSET_TYPE]): ?ElementInterface
    {

        if (!in_array($elementType, $typesToLookFor, true)) {
            return null;
        }

        return $this->getElement($elementType, $elementId);
    }
}
