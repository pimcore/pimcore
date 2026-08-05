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
