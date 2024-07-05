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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ValidateStepConfigurationTrait;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidStepConfigurationException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use UnexpectedValueException;

abstract class AbstractAutomationActionHandler
{
    use ValidateStepConfigurationTrait;

    protected ?LoggerInterface $genericExecutionEngineLogger = null;

    protected ?JobRunRepositoryInterface $jobRunRepository = null;

    private ?JobRunExtractorInterface $jobRunExtractor = null;

    private ?JobExecutionAgentInterface $jobExecutionAgent = null;

    private ?TranslatorInterface $translator = null;

    public function __construct(
    ) {
        $this->stepConfiguration = new OptionsResolver();
    }

    #[Required]
    public function setJobRunRepository(JobRunRepositoryInterface $jobRunRepository): void
    {
        if (!$this->jobRunRepository) {
            $this->jobRunRepository = $jobRunRepository;
        }
    }

    #[Required]
    public function setJobRunExtractor(JobRunExtractorInterface $jobRunExtractor): void
    {
        if (!$this->jobRunExtractor) {
            $this->jobRunExtractor = $jobRunExtractor;
        }
    }

    #[Required]
    public function setJobExecutionAgent(JobExecutionAgentInterface $jobExecutionAgent): void
    {
        if (!$this->jobExecutionAgent) {
            $this->jobExecutionAgent = $jobExecutionAgent;
        }
    }

    #[Required]
    public function setLogger(LoggerInterface $genericExecutionEngineLogger): void
    {
        if (!$this->genericExecutionEngineLogger) {
            $this->genericExecutionEngineLogger = $genericExecutionEngineLogger;
        }
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        if (!$this->translator) {
            $this->translator = $translator;
        }
    }

    /**
     * @template exceptionClassName of Exception
     *
     * @param class-string<exceptionClassName> $exceptionClassName
     *
     * @throws exceptionClassName
     */
    public function abortAction(
        string $translationKey,
        array $translationParams = [],
        string $translationDomain = 'default',
        string $exceptionClassName = Exception::class
    ): void {
        $errorMessage = $this->translator->trans($translationKey, $translationParams, $translationDomain);

        throw new $exceptionClassName($errorMessage);
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

    protected function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunExtractor->getJobRun($message, $forceReload);
    }

    protected function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface
    {
        return $this->jobRunExtractor->getJobStep($message);
    }

    protected function getCurrentJobStepConfig(GenericExecutionEngineMessageInterface $message): array
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
        GenericExecutionEngineMessageInterface $message,
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

    protected function isRunning(
        JobRun $jobRun
    ): bool {
        return $this->jobExecutionAgent->isRunning($jobRun->getId());
    }

    protected function getSubjectFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array $types = [JobRunExtractorInterface::OBJECT_TYPE, JobRunExtractorInterface::ASSET_TYPE]
    ): AbstractElement {
        /** @var AbstractElement|null $subject */
        $subject = $this->jobRunExtractor->getElementFromMessage(
            $message,
            $types
        );

        if (!$subject) {
            throw new UnexpectedValueException(
                sprintf(
                    'No subject type found. Expected types: %s, found %s, %s',
                    implode(', ', $types),
                    $message->getElement()?->getId(),
                    $message->getElement()?->getType()
                )
            );
        }

        return $subject;
    }
}
