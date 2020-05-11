<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        $directoryIterator = new \DirectoryIterator($dir);
        $filterIterator = new \CallbackFilterIterator($directoryIterator, function(\SplFileInfo $fileInfo) use ($thumbnail) {
            return $fileInfo->isDir() && (
                preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '$@', $fileInfo->getFilename()) ||
                preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '_auto_@', $fileInfo->getFilename())
            );
        });

        /** @var \SplFileInfo $fileInfo */
        foreach($filterIterator as $fileInfo) {
            recursiveDelete($fileInfo->getPathname());
        }
    }
}
