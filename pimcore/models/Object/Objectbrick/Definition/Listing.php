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
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Objectbrick\Definition;

use Pimcore\Model;

class Listing
{

    /**
     * @return array
     */
    public function load()
    {
        $fields = [];
        $objectBricksFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";
        $files = glob($objectBricksFolder . "/*.php");

        foreach ($files as $file) {
            $fields[] = include $file;
        }

        return $fields;
    }
}
