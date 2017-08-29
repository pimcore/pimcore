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
 * @package    DataObject\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Definition;

class Listing
{
    /**
     * @return array
     */
    public function load()
    {
        $fields = [];
        $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . '/fieldcollections';
        $files = glob($fieldCollectionFolder . '/*.php');

        foreach ($files as $file) {
            $fields[] = include $file;
        }

        return $fields;
    }
}
