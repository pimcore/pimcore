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

namespace Pimcore\Bundle\InstallBundle\Profile;

/**
 * Optional interface for install profiles that need to skip certain install steps.
 *
 * Profiles that implement this interface can declare which steps should be
 * excluded from the installation process. This is useful for PaaS environments
 * where certain steps (e.g., writing .env.local, installing assets) are handled
 * by the deployment pipeline rather than the installer.
 *
 * Profiles that don't need step filtering should simply not implement
 * this interface — all steps will execute normally.
 *
 * Note: Skipping steps with dependencies on other steps may cause failures.
 * See the documentation for step dependency information.
 */
interface InstallStepFilterInterface
{
    /**
     * Returns install steps that should be skipped during installation.
     *
     * @return InstallStep[]
     */
    public function getSkippedInstallSteps(): array;
}
