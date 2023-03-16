<?php

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

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Bundle\AdminBundle\Support\PimcoreBundleAdminSupportInterface;
use function dirname;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreFileExplorerBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminSupportInterface
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/file-explorer-bundle';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/css/file-explorer.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/js/startup.js',
            '/bundles/pimcorefileexplorer/js/explorer.js',
            '/bundles/pimcorefileexplorer/js/file.js',
        ];
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }
}
