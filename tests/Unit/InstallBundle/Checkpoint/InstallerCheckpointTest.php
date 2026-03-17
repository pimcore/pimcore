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

namespace Pimcore\Tests\Unit\InstallBundle\Checkpoint;

use Pimcore\Bundle\InstallBundle\Checkpoint\InstallerCheckpoint;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\InstallBundleTestHelperTrait;

/**
 * @internal
 */
final class InstallerCheckpointTest extends TestCase
{
    use InstallBundleTestHelperTrait;

    private string $tempDir;

    private string $checkpointPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_checkpoint_test_' . uniqid('', true);
        mkdir($this->tempDir);
        $this->checkpointPath = $this->tempDir . '/var/installer/progress.json';
    }

    protected function tearDown(): void
    {
        // Recursively clean up
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testNewCheckpointHasNoCompletedStep(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testMarkStepCompletedPersists(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $this->assertSame(12, $checkpoint->getCompletedStep());
        $this->assertTrue($checkpoint->exists());

        // Verify persistence by creating a new instance from the same directory
        $reloaded = new InstallerCheckpoint($this->tempDir);
        $this->assertSame(12, $reloaded->getCompletedStep());
    }

    public function testMarkMultipleStepsTracksLatest(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');
        $checkpoint->markStepCompleted(13, 'Admin user created');
        $checkpoint->markStepCompleted(14, 'Data imported');

        $this->assertSame(14, $checkpoint->getCompletedStep());
    }

    public function testMarkStepFailedDoesNotAdvanceCompletedStep(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');
        $checkpoint->markStepFailed(13, 'Connection refused');

        // completedStep should still be 12, not 13
        $this->assertSame(12, $checkpoint->getCompletedStep());
    }

    public function testExistsReturnsFalseBeforeAnyWrite(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        // New checkpoint hasn't written to disk yet (no steps completed)
        $this->assertFalse($checkpoint->exists());
    }

    public function testExistsReturnsTrueAfterStepCompleted(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $this->assertTrue($checkpoint->exists());
    }

    public function testRemoveClearsDataAndFile(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');
        $this->assertTrue($checkpoint->exists());

        $checkpoint->remove();

        $this->assertFalse($checkpoint->exists());
        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testRemoveOnNonExistentFileDoesNotThrow(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);

        // Should not throw
        $checkpoint->remove();

        $this->assertFalse($checkpoint->exists());
    }

    public function testResumeFromCheckpointFile(): void
    {
        // Simulate a previous run that completed step 15
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');
        $checkpoint->markStepCompleted(13, 'Admin user created');
        $checkpoint->markStepCompleted(14, 'Data imported');
        $checkpoint->markStepCompleted(15, 'Bundles registered');

        // Simulate a new run loading the checkpoint
        $resumed = new InstallerCheckpoint($this->tempDir);
        $this->assertSame(15, $resumed->getCompletedStep());
    }

    public function testCheckpointFileContainsValidJson(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $content = file_get_contents($this->checkpointPath);
        $this->assertNotFalse($content);

        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('completedStep', $decoded);
        $this->assertArrayHasKey('startedAt', $decoded);
        $this->assertArrayHasKey('updatedAt', $decoded);
        $this->assertArrayHasKey('stepResults', $decoded);
        $this->assertSame(12, $decoded['completedStep']);
    }

    public function testStepResultsContainStatusAndDetails(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');
        $checkpoint->markStepFailed(13, 'Connection refused');

        $content = file_get_contents($this->checkpointPath);
        $decoded = json_decode($content, true);

        $this->assertSame('completed', $decoded['stepResults'][12]['status']);
        $this->assertSame('Schema created', $decoded['stepResults'][12]['details']);
        $this->assertSame('failed', $decoded['stepResults'][13]['status']);
        $this->assertSame('Connection refused', $decoded['stepResults'][13]['details']);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        // Use a nested path that doesn't exist yet
        $nestedDir = $this->tempDir . '/deep/nested/project';
        $checkpoint = new InstallerCheckpoint($nestedDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $expectedPath = $nestedDir . '/var/installer/progress.json';
        $this->assertFileExists($expectedPath);
    }

    public function testCorruptedJsonFileStartsFresh(): void
    {
        // Write corrupted JSON to the checkpoint path
        $dir = dirname($this->checkpointPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->checkpointPath, '{invalid json!!!');

        $checkpoint = new InstallerCheckpoint($this->tempDir);

        // Should start fresh — no completed step
        $this->assertNull($checkpoint->getCompletedStep());
    }

    public function testStepResultsContainTimestamps(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepCompleted(12, 'Schema created');

        $content = file_get_contents($this->checkpointPath);
        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('startedAt', $decoded);
        $this->assertArrayHasKey('updatedAt', $decoded);

        // Timestamps should be valid ISO 8601
        $startedAt = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ATOM,
            $decoded['startedAt'],
        );
        $updatedAt = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ATOM,
            $decoded['updatedAt'],
        );
        $this->assertInstanceOf(\DateTimeImmutable::class, $startedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
    }

    public function testStepOverwritePreviousResult(): void
    {
        $checkpoint = new InstallerCheckpoint($this->tempDir);
        $checkpoint->markStepFailed(12, 'Connection refused');
        $checkpoint->markStepCompleted(12, 'Schema created');

        $content = file_get_contents($this->checkpointPath);
        $decoded = json_decode($content, true);

        // Step 12 should show completed (not failed), since it was overwritten
        $this->assertSame('completed', $decoded['stepResults'][12]['status']);
        $this->assertSame('Schema created', $decoded['stepResults'][12]['details']);
        $this->assertSame(12, $decoded['completedStep']);
    }

}
