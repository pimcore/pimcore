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

namespace Pimcore\Bundle\SystemInfoBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreSystemInfoBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    // @TODO Enable when bundle move to own repo
//
//    public function getComposerPackageName(): string
//    {
//        return 'pimcore/system-info-bundle';
//    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoresysteminfo/css/icons.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoresysteminfo/js/startup.js',
        ];
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
