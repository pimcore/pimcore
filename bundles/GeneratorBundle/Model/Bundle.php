<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\GeneratorBundle\Model;

class Bundle extends BaseBundle
{
    /**
     * @param $namespace
     * @param $name
     * @param $targetDirectory
     * @param $configurationFormat
     * @param $isShared
     */
    public function __construct($namespace, $name, $targetDirectory, $configurationFormat, $isShared)
    {
        parent::__construct($namespace, $name, $targetDirectory, $configurationFormat, $isShared);
    }

    public function shouldGenerateDependencyInjectionDirectory()
    {
        return true;
    }
}
