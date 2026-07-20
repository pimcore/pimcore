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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;
use Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage\MessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

#[Entity]
#[Table(name: 'generic_execution_engine_job_run')]
#[HasLifecycleCallbacks]
class JobRun
{
    public const DEFAULT_EXECUTION_CONTEXT = 'default';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: true)]
    private ?int $ownerId;

    #[ORM\Column(type: 'string', length: 10, enumType: JobRunStates::class)]
    private JobRunStates $state = JobRunStates::NOT_STARTED;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $currentStep = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $currentMessage;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $log;

    private ?SerializerInterface $serializer = null;

    private ?Job $job = null;

    #[ORM\Column(type: 'text')]
    private ?string $serializedJob;

    #[ORM\Column(type: 'json')]
    private ?array $context = null;

    #[ORM\Column(nullable: false)]
    private int $creationDate;

    #[ORM\Column(nullable: false)]
    private int $modificationDate;

    #[ORM\Column(type: 'string', length: 255)]
    private string $executionContext;

    #[ORM\Column(type: 'integer')]
    private int $totalElements = 0;

    #[ORM\Column(type: 'integer')]
    private int $processedElementsForStep = 0;

    public function __construct(?int $ownerId = null)
    {
        $this->creationDate = time();
        $this->modificationDate = time();
        $this->ownerId = $ownerId;
        $this->executionContext = self::DEFAULT_EXECUTION_CONTEXT;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function getState(): ?JobRunStates
    {
        return $this->state;
    }

    public function setState(?JobRunStates $state): void
    {
        $this->state = $state;
    }

    public function getCurrentStep(): ?int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(?int $currentStep): void
    {
        $this->currentStep = $currentStep;
    }

    public function getCurrentMessage(): string
    {
        if ($this->currentMessage) {
            return $this->currentMessage;
        }

        return '';
    }

    public function setCurrentMessage(?string $currentMessage): void
    {
        $this->currentMessage = $currentMessage;
    }

    /**
     * @return LogLine[]
     */
    public function getLogs(): array
    {
        if ($this->log === null) {
            return [];
        }

        /** @var LogLine[] $parsed */
        $parsed = [];

        $logLines = explode("\n", $this->log);

        foreach ($logLines as $line) {
            try {
                $logLine = new LogLine($line);
                $parsed[] = $logLine;
            } catch (InvalidArgumentException $e) {
                // not starting with a date, append to last parsed log line
                if (!empty($parsed)) {
                    $lastKey = array_key_last($parsed);
                    $parsed[$lastKey]->appendLogLine($line);
                }
            }
        }

        return $parsed;
    }

    public function setLog(?string $log): void
    {
        $this->log = $log;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function serializeJob(): void
    {
        if ($this->getJob() !== null) {
            $this->serializedJob = $this->getSerializer()->serialize($this->getJob(), 'json');
        } else {
            $this->serializedJob = null;
        }
    }

    #[ORM\PostLoad]
    public function deserializeJob(): void
    {
        if ($this->serializedJob) {
            $this->setJob($this->getSerializer()->deserialize($this->serializedJob, Job::class, 'json'));
        }
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(Job $job): void
    {
        $this->job = $job;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModificationDate(): void
    {
        $this->modificationDate = time();
    }

    public function getExecutionContext(): string
    {
        return $this->executionContext;
    }

    public function setExecutionContext(string $executionContext): void
    {
        $this->executionContext = $executionContext;
    }

    public function setCurrentMessageLocalized(MessageInterface $message): void
    {
        $this->setCurrentMessage($message->getSerializedString());
    }

    public function getTotalElements(): int
    {
        return $this->totalElements;
    }

    public function setTotalElements(int $totalElements): void
    {
        $this->totalElements = $totalElements;
    }

    public function getProcessedElementsForStep(): int
    {
        return $this->processedElementsForStep;
    }

    public function setProcessedElementsForStep(int $processedElementsForStep): void
    {
        $this->processedElementsForStep = $processedElementsForStep;
    }

    private function getSerializer(): SerializerInterface
    {
        if ($this->serializer === null) {
            $encoder = [
                new JsonEncoder(),
            ];
            $extractor = new PropertyInfoExtractor(
                [],
                [
                    new PhpDocExtractor(),
                    new ReflectionExtractor(),
                ]
            );
            $normalizer = [
                new ArrayDenormalizer(),
                new BackedEnumNormalizer(),
                new ObjectNormalizer(null, null, null, $extractor),
            ];
            $this->serializer = new Serializer(
                $normalizer,
                $encoder
            );
        }

        return $this->serializer;
    }
}
