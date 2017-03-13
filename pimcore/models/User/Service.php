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
 * @package    User
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User;

class Service
{

    /**
     * Mapping between database types and pimcore class names
     * @static
     * @param $type
     * @return string
     */
    public static function getClassNameForType($type)
    {
        switch ($type) {
            case "user": return "\\Pimcore\\Model\\User";
            case "userfolder": return "\\Pimcore\\Model\\User\\Folder";
            case "role": return "\\Pimcore\\Model\\User\\Role";
            case "rolefolder": return "\\Pimcore\\Model\\User\\Role\\Folder";
        }
    }
}
