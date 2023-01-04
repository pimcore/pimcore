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

namespace Pimcore\Bundle\SimpleBackendSearchBundle;

use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcoreSimpleBackendSearchBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoresimplebackendsearch/js/pimcore/startup.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/service.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector.js'
        ];
    }

    public function getInstaller(): ?InstallerInterface
    {
        return null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
