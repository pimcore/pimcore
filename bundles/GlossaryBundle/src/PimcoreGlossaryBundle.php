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

namespace Pimcore\Bundle\GlossaryBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

/**
 * @deprecated version 12.3
 */
class PimcoreGlossaryBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function __construct()
    {
        trigger_deprecation(
            'pimcore/glossary-bundle',
            '12.3',
            'The GlossaryBundle is deprecated and will be discontinued with Pimcore Studio.'
        );
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
