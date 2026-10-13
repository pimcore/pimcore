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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Pimcore;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\AbstractExecutionEngineMessage;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunElementCounter;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;

class JobRunElementCounterTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = Pimcore::getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * Two workers (independent database connections) count elements of the same job run. The
     * second worker holds a copy of the job run that has not seen the first increment, which
     * is what loses an increment when the counter is written from the entity.
     */
    public function testIncrementsOfIndependentWorkersAreRetained(): void
    {
        $jobRun = $this->createJobRun();
        $firstWorker = new JobRunElementCounter(Db::get(), $this->entityManager);
        $secondWorker = new JobRunElementCounter($this->createIndependentConnection(), $this->entityManager);

        $this->assertSame(1, $firstWorker->incrementProcessedElementsForStep($jobRun));

        $jobRun->setProcessedElementsForStep(0);

        $this->assertSame(2, $secondWorker->incrementProcessedElementsForStep($jobRun));
        $this->assertSame(2, $jobRun->getProcessedElementsForStep(), 'the entity is refreshed from the database');
        $this->assertSame(2, $this->loadProcessedElements($jobRun));
    }

    public function testAdvancesTheModificationDate(): void
    {
        $jobRun = $this->createJobRun();
        $outdated = time() - 3600;
        Db::get()->executeStatement(
            'UPDATE ' . TableConstants::JOB_RUN_TABLE . ' SET modificationDate = :modificationDate WHERE id = :id',
            ['modificationDate' => $outdated, 'id' => $jobRun->getId()]
        );
        $this->entityManager->refresh($jobRun);
        $this->assertSame($outdated, $jobRun->getModificationDate());

        (new JobRunElementCounter(Db::get(), $this->entityManager))->incrementProcessedElementsForStep($jobRun);

        $this->assertGreaterThan($outdated, $jobRun->getModificationDate());
    }

    private function createJobRun(): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setJob(
            new Job('test_job', [new JobStep('test_step', AbstractExecutionEngineMessage::class, '', [])])
        );
        $this->entityManager->persist($jobRun);
        $this->entityManager->flush();

        return $jobRun;
    }

    private function createIndependentConnection(): Connection
    {
        return DriverManager::getConnection(Db::get()->getParams());
    }

    private function loadProcessedElements(JobRun $jobRun): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT processedElementsForStep FROM ' . TableConstants::JOB_RUN_TABLE . ' WHERE id = :id',
            ['id' => $jobRun->getId()]
        );
    }
}
