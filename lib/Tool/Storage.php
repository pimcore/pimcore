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

namespace Pimcore\Tool;

use League\Flysystem\FilesystemOperator;
use Pimcore;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
class Storage
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getStorage(string $name): FilesystemOperator
    {
        return $this->locator->get(sprintf('pimcore.%s.storage', $name));
    }

    public static function get(string $name): FilesystemOperator
    {
        $storage = Pimcore::getContainer()->get(self::class);

        return $storage->getStorage($name);
    }
}
