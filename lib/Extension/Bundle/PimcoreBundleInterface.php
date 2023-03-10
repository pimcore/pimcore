<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Extension\Bundle;

use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Routing\RouteReferenceInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface PimcoreBundleInterface extends BundleInterface
{
    /**
     * Bundle name as shown in extension manager
     *
     * @return string
     */
    public function getNiceName(): string;

    /**
     * Bundle description as shown in extension manager
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Bundle version as shown in extension manager
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     *
     * @return InstallerInterface|null
     */
    public function getInstaller(): ?InstallerInterface;

    /**
     * Get javascripts to include in admin interface
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getJsPaths(): array;

    /**
     * Get stylesheets to include in admin interface
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getCssPaths(): array;

    /**
     * Get javascripts to include in editmode
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeJsPaths(): array;

    /**
     * Get stylesheets to include in editmode
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeCssPaths(): array;
}
