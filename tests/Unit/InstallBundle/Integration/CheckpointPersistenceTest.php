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

namespace Pimcore\Tests\Unit\InstallBundle\Integration;

use Pimcore\Bundle\InstallBundle\Checkpoint\InstallerCheckpoint;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Integration tests for checkpoint persistence and resume.
 *
 * Verifies that InstallerCheckpoint correctly persists progress to disk
 * and that a new InstallerCheckpoint instance can resume from the same
 * checkpoint file.
 *
 * @internal
 */
final class CheckpointPersistenceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_checkpoint_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testFreshCheckpointHasNoCompletedStep(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testFreshCheckpointDoesNotExistOnDisk(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        // Before marking any step, the checkpoint file should not exist yet
        // because no save() has been called
        $this->assertFalse($checkpoint->exists());
    }

    public function testMarkStepCompletedTracksProgress(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $checkpoint->markStepCompleted(12, 'Schema created');
        $this->assertSame(12, $checkpoint->getCompletedStep());

        $checkpoint->markStepCompleted(13, 'Admin user created');
        $this->assertSame(13, $checkpoint->getCompletedStep());
    }

    public function testMarkStepCompletedPersistsToDisk(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $checkpoint->markStepCompleted(12, 'Schema created');
        $this->assertTrue($checkpoint->exists());

        $checkpointPath = $this->tempDir . '/var/installer/progress.json';
        $this->assertFileExists($checkpointPath);

        $content = file_get_contents($checkpointPath);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertSame(12, $data['completedStep']);
        $this->assertArrayHasKey('startedAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertArrayHasKey('stepResults', $data);
        $this->assertSame('completed', $data['stepResults'][12]['status']);
        $this->assertSame('Schema created', $data['stepResults'][12]['details']);
    }

    public function testNewCheckpointInstanceResumesFromDisk(): void
    {
        // First instance: mark steps
        $checkpoint1 = new InstallerCheckpoint($this->tempDir);
        $checkpoint1->markStepCompleted(12, 'Schema created');
        $checkpoint1->markStepCompleted(13, 'Admin user created');
        $checkpoint1->markStepCompleted(14, 'Data imported');

        // Second instance: load from same path — should resume
        $checkpoint2 = new InstallerCheckpoint($this->tempDir);

        $this->assertSame(14, $checkpoint2->getCompletedStep());
        $this->assertTrue($checkpoint2->exists());
    }

    public function testResumePreservesAllStepResults(): void
    {
        $checkpoint1 = new InstallerCheckpoint($this->tempDir);
        $checkpoint1->markStepCompleted(12, 'Schema created');
        $checkpoint1->markStepCompleted(13, 'Admin created');
        $checkpoint1->markStepFailed(14, 'Import failed: file not found');

        // Read persisted state
        $checkpointPath = $this->tempDir . '/var/installer/progress.json';
        $data = json_decode(file_get_contents($checkpointPath), true);

        // completedStep should still be 13 (14 failed, not completed)
        $this->assertSame(13, $data['completedStep']);

        // All step results should be preserved
        $this->assertSame('completed', $data['stepResults'][12]['status']);
        $this->assertSame('completed', $data['stepResults'][13]['status']);
        $this->assertSame('failed', $data['stepResults'][14]['status']);
        $this->assertSame('Import failed: file not found', $data['stepResults'][14]['details']);

        // Second instance should also see completedStep = 13
        $checkpoint2 = new InstallerCheckpoint($this->tempDir);
        $this->assertSame(13, $checkpoint2->getCompletedStep());
    }

    public function testMarkStepFailedDoesNotAdvanceCompletedStep(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $checkpoint->markStepCompleted(12, 'Schema created');
        $checkpoint->markStepFailed(13, 'Connection lost');

        $this->assertSame(12, $checkpoint->getCompletedStep());
    }

    public function testResumeAfterFailureSkipsCompletedSteps(): void
    {
        // Simulate: steps 12, 13 complete, step 14 fails
        $checkpoint1 = new InstallerCheckpoint($this->tempDir);
        $checkpoint1->markStepCompleted(12, 'Schema created');
        $checkpoint1->markStepCompleted(13, 'Admin created');
        $checkpoint1->markStepFailed(14, 'Data import failed');

        // Resume: new instance should see completedStep = 13
        $checkpoint2 = new InstallerCheckpoint($this->tempDir);
        $this->assertSame(13, $checkpoint2->getCompletedStep());

        // Steps > 13 should still need to run
        // (the installer uses shouldRunStep() which checks step > completedStep)
    }

    public function testRemoveDeletesCheckpointFile(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $this->assertTrue($checkpoint->exists());

        $checkpoint->remove();

        $this->assertFalse($checkpoint->exists());
        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testRemoveOnFreshCheckpointDoesNotError(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->remove();

        // Should not throw
        $this->assertFalse($checkpoint->exists());
    }

    public function testCorruptedCheckpointFileStartsFresh(): void
    {
        // Write corrupted JSON
        $checkpointDir = $this->tempDir . '/var/installer';
        mkdir($checkpointDir, 0777, true);
        file_put_contents($checkpointDir . '/progress.json', '{invalid json!!!');

        $checkpoint = new InstallerCheckpoint($this->tempDir);

        // Should start fresh instead of crashing
        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testEmptyCheckpointFileStartsFresh(): void
    {
        $checkpointDir = $this->tempDir . '/var/installer';
        mkdir($checkpointDir, 0777, true);
        file_put_contents($checkpointDir . '/progress.json', '');

        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testCheckpointTracksStartedAtTimestamp(): void
    {
        $before = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $after = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $checkpointPath = $this->tempDir . '/var/installer/progress.json';
        $data = json_decode(file_get_contents($checkpointPath), true);

        $this->assertArrayHasKey('startedAt', $data);
        $this->assertGreaterThanOrEqual($before, $data['startedAt']);
        $this->assertLessThanOrEqual($after, $data['startedAt']);
    }

    public function testCheckpointUpdatesTimestampOnEachStep(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $checkpointPath = $this->tempDir . '/var/installer/progress.json';
        $data1 = json_decode(file_get_contents($checkpointPath), true);
        $updatedAt1 = $data1['updatedAt'];

        // Small delay to ensure timestamps differ
        usleep(10000); // 10ms

        $checkpoint->markStepCompleted(13, 'Admin created');
        $data2 = json_decode(file_get_contents($checkpointPath), true);
        $updatedAt2 = $data2['updatedAt'];

        $this->assertGreaterThanOrEqual($updatedAt1, $updatedAt2);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
