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

namespace Pimcore\Bundle\CustomReportsBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreCustomReportsBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return [];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorecustomreports/js/startup.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/abstract.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/broker.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/panel.js',
            '/bundles/pimcorecustomreports/js/pimcore/layout/portlets/customreports.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/settings.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/definitions/sql.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/item.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/panel.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/report.js',
            '/bundles/pimcorecustomreports/js/pimcore/report/custom/toolbarenricher.js',
        ];
    }

    public function getInstaller(): ?Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
