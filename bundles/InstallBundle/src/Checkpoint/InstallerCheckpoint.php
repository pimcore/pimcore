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

namespace Pimcore\Bundle\InstallBundle\Checkpoint;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tracks installation progress for resume-on-failure.
 *
 * Writes progress to var/installer/progress.json. On re-run, the installer
 * can resume from the last completed step instead of starting over.
 *
 * @internal
 */
final class InstallerCheckpoint
{
    private readonly Filesystem $filesystem;

    private readonly string $checkpointPath;

    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(string $projectRoot)
    {
        $this->filesystem = new Filesystem();
        $this->checkpointPath = $projectRoot . '/var/installer/progress.json';
        $this->load();
    }

    public function getCompletedStep(): ?int
    {
        return $this->data['completedStep'] ?? null;
    }

    public function markStepCompleted(int $step, string $details = ''): void
    {
        $this->recordStep($step, 'completed', $details);
        $this->data['completedStep'] = $step;
        $this->save();
    }

    public function markStepFailed(int $step, string $details): void
    {
        $this->recordStep($step, 'failed', $details);
        $this->save();
    }

    public function exists(): bool
    {
        return $this->filesystem->exists($this->checkpointPath);
    }

    public function remove(): void
    {
        if ($this->filesystem->exists($this->checkpointPath)) {
            $this->filesystem->remove($this->checkpointPath);
        }
        $this->data = [];
    }

    private function recordStep(int $step, string $status, string $details): void
    {
        $this->data['updatedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $this->data['stepResults'][$step] = [
            'status' => $status,
            'details' => $details,
        ];
    }

    private function load(): void
    {
        if ($this->filesystem->exists($this->checkpointPath)) {
            $content = file_get_contents($this->checkpointPath);
            if ($content !== false) {
                try {
                    $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded) && $this->isValidStructure($decoded)) {
                        $this->data = $decoded;
                    }
                } catch (JsonException) {
                    // Corrupted checkpoint file — start fresh
                }
            }
        }

        if (!isset($this->data['startedAt'])) {
            $this->data['startedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
            $this->data['stepResults'] = [];
        }
    }

    private function isValidStructure(array $data): bool
    {
        if (!isset($data['startedAt']) || !is_string($data['startedAt'])) {
            return false;
        }

        if (!isset($data['stepResults']) || !is_array($data['stepResults'])) {
            return false;
        }

        return true;
    }

    private function save(): void
    {
        $dir = dirname($this->checkpointPath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $encoded = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        $this->filesystem->dumpFile($this->checkpointPath, $encoded);
    }
}
