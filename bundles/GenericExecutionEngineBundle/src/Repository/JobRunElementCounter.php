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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;

/**
 * @internal
 */
final readonly class JobRunElementCounter implements JobRunElementCounterInterface
{
    public function __construct(
        private Connection $db,
        private EntityManagerInterface $pimcoreEntityManager,
    ) {
    }

    /**
     * @throws Exception
     * @throws ORMException
     */
    public function incrementProcessedElementsForStep(JobRun $jobRun): int
    {
        $processedElements = $this->db->transactional(
            static function (Connection $connection) use ($jobRun): int {
                $processedElements = (int) $connection->fetchOne(
                    'SELECT processedElementsForStep FROM ' . TableConstants::JOB_RUN_TABLE .
                    ' WHERE id = :id FOR UPDATE',
                    ['id' => $jobRun->getId()]
                ) + 1;

                $connection->executeStatement(
                    'UPDATE ' . TableConstants::JOB_RUN_TABLE .
                    ' SET processedElementsForStep = :processedElements, modificationDate = :modificationDate' .
                    ' WHERE id = :id',
                    [
                        'processedElements' => $processedElements,
                        'modificationDate' => time(),
                        'id' => $jobRun->getId(),
                    ]
                );

                return $processedElements;
            }
        );

        // the entity may be stale after the direct update
        $this->pimcoreEntityManager->refresh($jobRun);

        return $processedElements;
    }
}
