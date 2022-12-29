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

namespace Pimcore\Bundle\Web2PrintBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreWeb2PrintBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return [

        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoreweb2print/js/startup.js',
            '/bundles/pimcoreweb2print/js/web2print.js',
            '/bundles/pimcoreweb2print/js/document/printabstract.js',
            '/bundles/pimcoreweb2print/js/document/printcontainer.js',
            '/bundles/pimcoreweb2print/js/document/printpage.js',
            "/bundles/pimcoreweb2print/js/document/printpages/pdf_preview.js",
        ];
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }


    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
