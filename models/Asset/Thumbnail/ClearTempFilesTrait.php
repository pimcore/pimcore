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
        $dirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (
                preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '$@', $dir) ||
                preg_match('@/(image|video)\-thumb__[\d]+__' . $thumbnail . '_auto_@', $dir)
            ) {
                recursiveDelete($dir);
            }
            $this->recursiveDelete($dir, $thumbnail, $matches);
        }

        return $matches;
    }
}
