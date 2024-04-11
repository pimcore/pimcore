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

namespace Pimcore\Bundle\JobExecutionEngineBundle\Extractor;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Helper\SymfonyExpression\ExpressionServiceInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;

final class JobRunExtractor implements JobRunExtractorInterface
{
    public const ASSET_TYPE = 'asset';

    public const DOCUMENT_TYPE = 'document';

    public const FOLDER_TYPE = 'folder';

    public const OBJECT_TYPE = 'object';

    public function __construct(
        private readonly ExpressionServiceInterface $symfonyExpressionService,
        private readonly JobRunRepositoryInterface $jobRunRepository
    ) {
    }

    public function getElementsToProcess(JobRun $jobRun, string $type = self::ASSET_TYPE): array
    {
        $elementsToProcess = [];
        $subject = $jobRun->getJob()?->getSubject();
        $selectedElements = $jobRun->getJob()?->getSelectedElements() ?? [];

        if (empty($selectedElements) && $subject && $subject->getType() === $type) {
            $elementsToProcess[] = $this->getElement($type, $subject->getId());
        }

        foreach ($selectedElements as $selectedElement) {
            if ($selectedElement && $selectedElement->getType() === $type) {
                $elementsToProcess[] = $this->getElement($type, $selectedElement->getId());
            }
        }

        return array_filter($elementsToProcess);
    }

    public function getJobRun(JobExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunRepository->getJobRunById($message->getJobRunId(), $forceReload);
    }

    public function getJobStep(JobExecutionEngineMessageInterface $message): JobStepInterface
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

    public function checkCondition(JobExecutionEngineMessageInterface $message): bool
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

    private function getElement(string $type, int $id): ?ElementInterface
    {
        $element = Service::getElementById($type, $id);

        if (!$element || $element->getType() === self::FOLDER_TYPE) {
            return null;
        }

        return $element;
    }
}
