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

namespace Pimcore\Bundle\InstallBundle;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Stateless service for merging, filtering, and validating env var definitions
 * and profile bundles.
 *
 * @internal
 */
final class DefinitionResolver
{
    /**
     * Merge profile definitions with CLI-provided definitions.
     * CLI definitions override profile definitions on key collision.
     *
     * @param list<EnvVarDefinitionInterface> $profileDefs
     * @param list<EnvVarDefinitionInterface> $extraDefs
     *
     * @return array<string, EnvVarDefinitionInterface> key => definition
     */
    public function mergeDefinitions(array $profileDefs, array $extraDefs): array
    {
        $merged = [];

        foreach ($profileDefs as $def) {
            $merged[$def->getKey()] = $def;
        }

        foreach ($extraDefs as $def) {
            $merged[$def->getKey()] = $def;
        }

        return $merged;
    }

    /**
     * Apply --skip flags: remove definitions matching skipped keys.
     * Returns errors if trying to skip a required definition.
     *
     * @param array<string, EnvVarDefinitionInterface> $definitions modified in place
     * @param list<string> $skipKeys
     *
     * @return list<string> errors
     */
    public function applySkipFlags(array &$definitions, array $skipKeys): array
    {
        $errors = [];

        foreach ($skipKeys as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }

            if ($definitions[$key]->isRequired()) {
                $errors[] = sprintf(
                    'Cannot skip required definition "%s" (%s)',
                    $key,
                    $definitions[$key]->getLabel(),
                );

                continue;
            }

            unset($definitions[$key]);
        }

        return $errors;
    }

    /**
     * Check installed Composer packages for Pimcore bundles not in the profile.
     * This is informational only — no error, just a warning.
     */
    public function warnOnMissingBundles(
        InstallProfileInterface $profile,
        SymfonyStyle $io,
        string $projectRoot,
    ): void {
        $installedJsonPath = $projectRoot . '/vendor/composer/installed.json';
        if (!file_exists($installedJsonPath)) {
            return;
        }

        $content = file_get_contents($installedJsonPath);
        if ($content === false) {
            return;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return;
        }

        // installed.json can be either {packages: [...]} or [...]
        $packages = $decoded['packages'] ?? $decoded;
        if (!is_array($packages)) {
            return;
        }

        $profileBundles = $profile->getBundles();
        $pimcoreBundlePackages = [];

        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            $type = $package['type'] ?? '';

            if ($type !== 'pimcore-bundle' || !is_string($name)) {
                continue;
            }

            $extra = $package['extra'] ?? [];
            $bundleClasses = $extra['pimcore']['bundles'] ?? [];
            if (!is_array($bundleClasses)) {
                continue;
            }

            foreach ($bundleClasses as $bundleClass) {
                if (!in_array($bundleClass, $profileBundles, true)) {
                    $pimcoreBundlePackages[$name] = $bundleClass;
                }
            }
        }

        if ($pimcoreBundlePackages !== []) {
            $io->warning(sprintf(
                'The following Pimcore bundles are installed via Composer but not '
                . "included in this profile:\n  %s",
                implode("\n  ", array_map(
                    static fn (string $pkg, string $cls) => sprintf('%s (%s)', $pkg, $cls),
                    array_keys($pimcoreBundlePackages),
                    array_values($pimcoreBundlePackages),
                )),
            ));
        }
    }

    /**
     * Validate that all bundle FQCNs from the profile are loadable.
     *
     * @return list<string> errors
     */
    public function validateProfileBundles(InstallProfileInterface $profile): array
    {
        $errors = [];

        foreach ($profile->getBundles() as $bundleFqcn) {
            if (!class_exists($bundleFqcn)) {
                $errors[] = sprintf(
                    'Bundle class "%s" from profile "%s" does not exist. '
                    . 'Is the package installed via Composer?',
                    $bundleFqcn,
                    $profile->getName(),
                );
            }
        }

        return $errors;
    }

    /**
     * Validate that the profile contains exactly one implementation of each
     * required definition category (search engine, messenger transport).
     *
     * @param array<string, EnvVarDefinitionInterface> $definitions
     *
     * @return list<string> errors
     */
    public function validateDefinitionCategories(array $definitions): array
    {
        $errors = [];

        $searchEngineCount = 0;
        $messengerTransportCount = 0;

        foreach ($definitions as $definition) {
            if ($definition instanceof SearchEngineDefinitionInterface) {
                $searchEngineCount++;
            }
            if ($definition instanceof MessengerTransportDefinitionInterface) {
                $messengerTransportCount++;
            }
        }

        if ($searchEngineCount === 0) {
            $errors[] = 'Profile must include exactly one SearchEngineDefinitionInterface '
                . 'implementation (e.g., OpenSearchEnvVarDefinition or ElasticsearchEnvVarDefinition).';
        } elseif ($searchEngineCount > 1) {
            $errors[] = sprintf(
                'Profile must include exactly one SearchEngineDefinitionInterface '
                . 'implementation, but found %d.',
                $searchEngineCount,
            );
        }

        if ($messengerTransportCount === 0) {
            $errors[] = 'Profile must include exactly one MessengerTransportDefinitionInterface '
                . 'implementation (e.g., DoctrineMessengerEnvVarDefinition or '
                . 'RabbitMqMessengerEnvVarDefinition).';
        } elseif ($messengerTransportCount > 1) {
            $errors[] = sprintf(
                'Profile must include exactly one MessengerTransportDefinitionInterface '
                . 'implementation, but found %d.',
                $messengerTransportCount,
            );
        }

        return $errors;
    }

    /**
     * @param array<string, EnvVarDefinitionInterface> $definitions
     */
    public function displayDefinitionSummary(array $definitions, SymfonyStyle $io): void
    {
        $io->text('<info>Definitions:</info>');

        foreach ($definitions as $definition) {
            $marker = $definition->isRequired() ? "\u{25CF}" : "\u{25CB}";
            $label = $definition->isRequired() ? 'required' : 'optional';
            $io->text(sprintf('  %s %-20s %s', $marker, $definition->getKey(), $label));
        }

        $io->newLine();
    }
}
