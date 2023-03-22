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

namespace Pimcore\Bundle\GoogleMarketingBundle;

use Pimcore\Bundle\AdminBundle\Support\BundleAdminSupportTrait;
use Pimcore\Bundle\AdminBundle\Support\PimcoreBundleAdminSupportInterface;
use Pimcore\Bundle\CustomReportsBundle\PimcoreCustomReportsBundle;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class PimcoreGoogleMarketingBundle extends AbstractPimcoreBundle implements DependentBundleInterface, PimcoreBundleAdminSupportInterface
{
    use BundleAdminSupportTrait;
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/google-marketing-bundle';
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoregooglemarketing/css/googlemarketing.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoregooglemarketing/js/startup.js',
            '/bundles/pimcoregooglemarketing/js/settings.js',
            '/bundles/pimcoregooglemarketing/js/report/analytics/elementexplorer.js',
            '/bundles/pimcoregooglemarketing/js/report/analytics/elementoverview.js',
            '/bundles/pimcoregooglemarketing/js/report/analytics/settings.js',
            '/bundles/pimcoregooglemarketing/js/report/custom/definitions/analytics.js',
            '/bundles/pimcoregooglemarketing/js/report/tagmanager/settings.js',
            '/bundles/pimcoregooglemarketing/js/report/webmastertools/settings.js',
            '/bundles/pimcoregooglemarketing/js/layout/portlets/analytics.js',
        ];
    }

    public function getInstaller(): ?Installer\InstallerInterface
    {
        /** @var \Pimcore\Bundle\GoogleMarketingBundle\Installer $installer */
        $installer = $this->container->get(\Pimcore\Bundle\GoogleMarketingBundle\Installer::class);

        return $installer;
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(PimcoreCustomReportsBundle::class, 20);
    }
}
