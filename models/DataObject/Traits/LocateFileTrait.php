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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

/**
 * @internal
 */
trait LocateFileTrait
{
    protected function locateFile(string $key, string $pathTemplate): string
    {
        $customFile = sprintf('%s/classes/' . $pathTemplate,
            PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY, $key);

        if (is_file($customFile)) {
            return $customFile;
        } else {
            return sprintf('%s/' . $pathTemplate,
                PIMCORE_CLASS_DIRECTORY, $key);
        }
    }
}
