<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Install\Profile;

use Pimcore\Composer;
use Pimcore\Install\Profile\Configuration\ManifestConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ProfileLocator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Composer\PackageInfo
     */
    private $packageInfo;

    /**
     * @var Profile[]
     */
    private $profiles;

    /**
     * @var Processor
     */
    private $configProcessor;

    /**
     * @var ManifestConfiguration
     */
    private $configuration;

    /**
     * @param LoggerInterface $logger
     * @param Composer\PackageInfo $packageInfo
     */
    public function __construct(LoggerInterface $logger, Composer\PackageInfo $packageInfo)
    {
        $this->logger      = $logger;
        $this->packageInfo = $packageInfo;
    }

    public function getProfile(string $id): Profile
    {
        $profiles = $this->getProfiles();
        if (!isset($profiles[$id])) {
            throw new \InvalidArgumentException(sprintf('Profile "%s" does not exist', $id));
        }

        return $profiles[$id];
    }

    /**
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        if (null === $this->profiles) {
            $this->profiles = array_merge(
                $this->findLocalProfiles(),
                $this->findComposerProfiles()
            );
        }

        return $this->profiles;
    }

    /**
     * @return Profile[]
     */
    private function findLocalProfiles(): array
    {
        $installProfilesPath = PIMCORE_PROJECT_ROOT . '/install-profiles';

        $finder = new Finder();
        $finder
            ->followLinks()
            ->in($installProfilesPath)
            ->name('manifest.yml');

        $profiles = [];
        foreach ($finder as $manifest) {
            $directory = new \SplFileInfo(dirname($manifest->getRealPath()));
            $profileId = $directory->getBasename();

            try {
                $profiles[$profileId] = $this->buildProfile($profileId, $manifest->getRealPath());
            } catch (\Throwable $e) {
                $this->logger->error('Failed to build profile {profile}: {exception}', [
                    'profile'   => $profileId,
                    'exception' => $e
                ]);
            }
        }

        return $profiles;
    }

    /**
     * @return Profile[]
     */
    private function findComposerProfiles(): array
    {
        $packages = $this->packageInfo->getInstalledPackages('pimcore-install-profile');

        $profiles = [];
        foreach ($packages as $package) {
            $path = PIMCORE_COMPOSER_PATH . '/' . $package['name'];

            $manifestPath = $path . '/manifest.yml';
            if (isset($package['extra']) && isset($package['extra']['pimcore'])) {
                if (isset($package['extra']['pimcore']['manifest'])) {
                    $manifestPath = $package['extra']['pimcore']['manifest'];
                    $manifestPath = realpath($path . '/' . $manifestPath);

                    if (file_exists($manifestPath)) {
                        if (0 !== strpos($manifestPath, $path)) {
                            throw new \RuntimeException(sprintf(
                                'Manifest path for package "%s" resolves to "%s", but the path is outside the package directory',
                                $package['name'],
                                $manifestPath
                            ));
                        }
                    }
                }
            }

            if (!file_exists($manifestPath)) {
                continue;
            }

            $id = $package['name'];

            try {
                $profiles[$id] = $this->buildProfile($id, $manifestPath);
            } catch (\Throwable $e) {
                $this->logger->error($e);
            }
        }

        return $profiles;
    }

    private function buildProfile(string $id, string $manifestPath): Profile
    {
        $config = Yaml::parse(file_get_contents($manifestPath));
        $config = $this->processProfileConfiguration($config);

        return new Profile($id, dirname($manifestPath), $config);
    }

    private function processProfileConfiguration(array $config)
    {
        if (null === $this->configProcessor) {
            $this->configProcessor = new Processor();
            $this->configuration   = new ManifestConfiguration();
        }

        return $this->configProcessor->processConfiguration(
            $this->configuration,
            [$config]
        );
    }
}
