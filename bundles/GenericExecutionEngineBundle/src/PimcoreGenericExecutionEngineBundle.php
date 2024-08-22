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

namespace Pimcore\Bundle\GenericExecutionEngineBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreGenericExecutionEngineBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getInstaller(): InstallerInterface
    {
        /** @var InstallerInterface|null */
        return $this->container->get(Installer::class);
    }
}
