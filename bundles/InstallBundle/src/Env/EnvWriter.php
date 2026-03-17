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

namespace Pimcore\Bundle\InstallBundle\Env;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes env vars to .env.local using Symfony Flex-style section markers.
 *
 * Section format:
 *   ###> section-name ###
 *   KEY="value"
 *   ###< section-name ###
 *
 * @internal
 */
final readonly class EnvWriter
{
    private Filesystem $filesystem;

    public function __construct(
        private string $envFilePath,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Write env vars grouped by section name.
     *
     * @param array<string, array<string, string>> $sectionedEnvVars
     *        section name => [env var name => value]
     *
     * @return list<string> warnings (duplicate vars, malformed markers, etc.)
     */
    public function write(array $sectionedEnvVars): array
    {
        $warnings = [];
        $existingContent = '';

        if ($this->filesystem->exists($this->envFilePath)) {
            $existingContent = file_get_contents($this->envFilePath);
            if ($existingContent === false) {
                throw new RuntimeException(sprintf(
                    'Failed to read existing env file: %s',
                    $this->envFilePath,
                ));
            }
        }

        // Track all env var names to detect duplicates across sections
        $allEnvVars = [];
        foreach ($sectionedEnvVars as $sectionName => $envVars) {
            foreach ($envVars as $varName => $value) {
                if (isset($allEnvVars[$varName])) {
                    $warnings[] = sprintf(
                        'Env var "%s" appears in both sections "%s" and "%s". Last section wins.',
                        $varName,
                        $allEnvVars[$varName],
                        $sectionName,
                    );
                }
                $allEnvVars[$varName] = $sectionName;
            }
        }

        $content = $existingContent;

        foreach ($sectionedEnvVars as $sectionName => $envVars) {
            $sectionContent = $this->buildSectionContent($envVars);
            $openMarker = '###> ' . $sectionName . ' ###';
            $closeMarker = '###< ' . $sectionName . ' ###';

            $openPos = strpos($content, $openMarker);
            $closePos = strpos($content, $closeMarker);

            if ($openPos !== false && $closePos !== false && $closePos > $openPos) {
                // Replace existing section
                $before = substr($content, 0, $openPos);
                $after = substr($content, $closePos + strlen($closeMarker));

                // Trim trailing newline from 'after' to avoid double blank lines
                if (str_starts_with($after, "\n")) {
                    $after = substr($after, 1);
                }

                $content = $before
                    . $openMarker . "\n"
                    . $sectionContent
                    . $closeMarker . "\n"
                    . $after;
            } elseif ($openPos !== false || $closePos !== false) {
                // Malformed: one marker without the other
                $warnings[] = sprintf(
                    'Malformed section markers for "%s" in %s. Appending new section.',
                    $sectionName,
                    $this->envFilePath,
                );
                $content = $this->appendSection(
                    $content,
                    $openMarker,
                    $closeMarker,
                    $sectionContent,
                );
            } else {
                // New section — append
                $content = $this->appendSection(
                    $content,
                    $openMarker,
                    $closeMarker,
                    $sectionContent,
                );
            }
        }

        // Ensure trailing newline
        if ($content !== '' && !str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        $this->filesystem->dumpFile($this->envFilePath, $content);

        return $warnings;
    }

    /**
     * @param array<string, string> $envVars
     */
    private function buildSectionContent(array $envVars): string
    {
        $lines = [];
        foreach ($envVars as $name => $value) {
            $lines[] = $name . '="' . $this->escapeValue($value) . '"';
        }

        return implode("\n", $lines) . "\n";
    }

    private function escapeValue(string $value): string
    {
        // Escape backslashes, double quotes, and dollar signs for .env file format.
        // Dollar signs must be escaped because dotenv parsers treat $VAR and ${VAR}
        // as variable references inside double-quoted values.
        return str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
    }

    private function appendSection(
        string $content,
        string $openMarker,
        string $closeMarker,
        string $sectionContent,
    ): string {
        // Ensure blank line separator before new section (if content is non-empty).
        // Normalize to exactly two trailing newlines so sections are visually separated.
        if ($content !== '') {
            $content = rtrim($content, "\n") . "\n\n";
        }

        return $content
            . $openMarker . "\n"
            . $sectionContent
            . $closeMarker . "\n";
    }
}
