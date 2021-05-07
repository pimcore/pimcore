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

namespace Pimcore\Model\Asset\Thumbnail;

trait ClearTempFilesTrait
{
    public function doClearTempFiles($rootDir, $name)
    {
        $this->recursiveDelete($rootDir, $name);
    }

    protected function recursiveDelete($dir, $thumbnail, &$matches = [])
    {
        if (!is_dir($dir)) {
            return [];
        }

        $directoryIterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD) as $fileInfo) {
            if ($fileInfo->isDir()) {
                if (
                    preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '$@', $fileInfo->getPathname(), $matches) ||
                    preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '_auto_@', $fileInfo->getPathname(), $matches)
                ) {
                    recursiveDelete($fileInfo->getPathname());
                }
            }
        }

        return $matches;
    }
}
