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

namespace Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Handler;

use Exception;
use Pimcore\Bundle\JobExecutionEngineBundle\Configuration\ValidateStepConfigurationTrait;
use Pimcore\Bundle\JobExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\JobExecutionEngineBundle\Exception\InvalidStepConfigurationException;
use Pimcore\Bundle\JobExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Model\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\Attribute\Required;
use Throwable;

abstract class AbstractAutomationActionHandler
{
    use ValidateStepConfigurationTrait;

    protected LoggerInterface $logger;

    protected JobRunRepositoryInterface $jobRunRepository;

    public function __construct(
        private readonly JobRunExtractorInterface $jobRunExtractor
    ) {
        $this->stepConfiguration = new OptionsResolver();
    }

    #[Required]
    public function setJobRunRepository(JobRunRepositoryInterface $jobRunRepository): void
    {
        $this->jobRunRepository = $jobRunRepository;
    }

    #[Required]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Logs a translation key to the job run which can be viewed in the job run overview
     * Translation key then can be translated check pimcore_job_execution.en.yaml
     *
     */
    protected function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array $params = []
    ): void {
        $this->jobRunExtractor->logMessageToJobRun($jobRun, $translationKey, $params);
    }

    protected function getJobRun(JobExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunExtractor->getJobRun($message, $forceReload);
    }

    protected function getJobStep(JobExecutionEngineMessageInterface $message): JobStepInterface
    {
        return $this->jobRunExtractor->getJobStep($message);
    }

    protected function getCurrentJobStepConfig(JobExecutionEngineMessageInterface $message): array
    {
        $jobStep = $this->getJobStep($message);

        $config = $jobStep->getConfig();

        try {
            $this->configureStep();
            $config = $this->resolveStepConfiguration($config);
        } catch (Exception $e) {
            throw new InvalidStepConfigurationException($e->getMessage());
        }

        return $config;
    }

    protected function extractConfigFieldFromJobStepConfig(
        JobExecutionEngineMessageInterface $message,
        string $key
    ): mixed {
        $config = $this->getCurrentJobStepConfig($message);

        if (!array_key_exists($key, $config)) {
            throw new NotFoundException("Missing configuration $key");
        }

        return $config[$key];
    }

    protected function updateJobRunContext(
        JobRun $jobRun,
        string $key,
        mixed $value,
    ): void {
        $context = $jobRun->getContext();
        $context[$key] = $value;
        $jobRun->setContext($context);
        $this->jobRunRepository->update($jobRun);
    }

    protected function throwUnRecoverableException(Throwable $exception): void
    {
        throw new UnrecoverableMessageHandlingException($exception->getMessage(), 0, $exception);
    }
}
