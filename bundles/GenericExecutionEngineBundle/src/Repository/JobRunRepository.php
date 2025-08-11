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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Security\PermissionServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;

final readonly class JobRunRepository implements JobRunRepositoryInterface
{
    public function __construct(
        private Connection $db,
        private CurrentMessageProviderInterface $currentMessageProvider,
        private EntityManagerInterface $pimcoreEntityManager,
        private ExecutionContextInterface $executionContext,
        private LoggerInterface $genericExecutionEngineLogger,
        private PermissionServiceInterface $permissionService,
        private Translator $translator,
    ) {
    }

    public function createFromJob(Job $job, ?int $ownerId = null): JobRun
    {
        $jobRun = new JobRun($ownerId);

        $jobRun->setJob($job);

        $this->pimcoreEntityManager->persist($jobRun);
        $this->pimcoreEntityManager->flush();

        return $jobRun;
    }

    public function update(JobRun $jobRun): JobRun
    {
        $this->pimcoreEntityManager->persist($jobRun);
        $this->pimcoreEntityManager->flush();

        return $jobRun;
    }

    /**
     * @throws Exception
     * @throws ORMException
     *
     * @internal
     */
    public function updateLogLocalizedWithDomain(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en',
        string $domain = 'admin'
    ): void {
        if ($updateCurrentMessage) {
            $jobRun->setCurrentMessageLocalized(
                $this->currentMessageProvider->getTranslationMessages($message, $params, $domain)
            );
            $this->update($jobRun);
        }

        $translatedMessage = $this->translator->trans($message, $params, $domain, $defaultLocale);
        $this->updateLog($jobRun, $translatedMessage);
    }

    /**
     * @throws Exception|ORMException
     */
    public function updateLogLocalized(
        JobRun $jobRun,
        string $message,
        array $params = [],
        bool $updateCurrentMessage = true,
        string $defaultLocale = 'en'
    ): void {
        $domain = $this->executionContext->getTranslationDomain($jobRun->getExecutionContext());

        $this->updateLogLocalizedWithDomain(
            $jobRun,
            $message,
            $params,
            $updateCurrentMessage,
            $defaultLocale,
            $domain
        );
    }

    /**
     * @throws Exception
     * @throws ORMException
     */
    public function updateLog(JobRun $jobRun, string $message): void
    {

        $this->db->executeStatement(
            'UPDATE ' .
            TableConstants::JOB_RUN_TABLE .
            ' SET log = IF(ISNULL(log),:message,CONCAT(log, "\n", :message)) WHERE id = :id',
            [
                'id' => $jobRun->getId(),
                'message' => (new DateTimeImmutable())->format('c') . ': ' . trim($message),
            ]
        );

        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: " . $message);

        $this->pimcoreEntityManager->refresh($jobRun);
    }

    /**
     * @throws ORMException
     */
    public function getJobRunById(
        int $id,
        bool $forceReload = false,
        ?int $ownerId = null
    ): JobRun {
        $params = ['id' => $id];
        $params = $this->setOwnerId($params, $ownerId);

        $jobRun = $this->pimcoreEntityManager->getRepository(JobRun::class)->findOneBy($params);
        if (!$jobRun) {
            throw new NotFoundException("JobRun with id $id not found.");
        }

        if ($forceReload) {
            $this->pimcoreEntityManager->refresh($jobRun);
        }

        return $jobRun;

    }

    /**
     * Get all job runs by user id. If user has permission to see all job runs, all job runs will be returned.
     *
     * @return JobRun[]
     *
     */
    public function getJobRunsByUserId(
        ?int $ownerId = null,
        array $orderBy = [],
        int $limit = 100,
        int $offset = 0,
        ?string $executionContext = null
    ): array {
        $params = [];
        $params = $this->setOwnerId($params, $ownerId);
        $params = $this->setExecutionContext($params, $executionContext);

        return $this->pimcoreEntityManager->getRepository(JobRun::class)->findBy(
            $params,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getTotalCount(): int
    {
        return $this->pimcoreEntityManager->getRepository(JobRun::class)->count();
    }

    public function getRunningJobsByUserId(
        int $ownerId,
        array $orderBy = [],
        int $limit = 10,
        ?string $executionContext = null
    ): array {
        $params = [];
        $params = $this->setOwnerId($params, $ownerId);
        $params = $this->setExecutionContext($params, $executionContext);
        $params['state'] = JobRunStates::RUNNING;

        return $this->pimcoreEntityManager
            ->getRepository(JobRun::class)
            ->findBy(
                $params,
                $orderBy,
                $limit
            );
    }

    public function getLastJobRunByName(string $name): ?JobRun
    {
        $result = $this->pimcoreEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('JobRun')
            ->where('JobRun.serializedJob LIKE :name')
            ->setParameter('name', '%name":"' . $name . '"%')
            ->orderBy('JobRun.modificationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    /**
     * @throws Exception|ORMException
     */
    public function updateSelectedElements(JobRun $jobRun, array $selectedElements): void
    {
        $job = $jobRun->getJob();
        if (!$job) {
            throw new JobNotFoundException('Job not found for JobRun with id: ' . $jobRun->getId());
        }
        $currentlySelectedElements = $job->getSelectedElements();
        $job->setSelectedElements($selectedElements);
        $this->update($jobRun);
        $this->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_updated_selected_elements',
            [
                '%fromCount%' => count($currentlySelectedElements),
                '%toCount%' => count($selectedElements),
            ]
        );
    }

    private function setExecutionContext(array $params, ?string $executionContext): array
    {
        if ($executionContext) {
            $params['executionContext'] = $executionContext;
        }

        return $params;
    }

    private function setOwnerId(array $params, ?int $ownerId): array
    {
        if ($ownerId !== null && !$this->permissionService->isAllowedToSeeAllJobRuns()) {
            $params['ownerId'] = $ownerId;
        }

        return $params;
    }
}
