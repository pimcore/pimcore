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

namespace Pimcore\Extension\Bundle;

use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface PimcoreBundleInterface extends BundleInterface
{
    /**
     * Bundle name as shown in extension manager
     *
     */
    public function getNiceName(): string;

    /**
     * Bundle description as shown in extension manager
     *
     */
    public function getDescription(): string;

    /**
     * Bundle version as shown in extension manager
     *
     */
    public function getVersion(): string;

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     *
     */
    public function getInstaller(): ?InstallerInterface;
}
