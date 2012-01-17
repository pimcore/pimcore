<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Service {

    /**
     * Mapping between database types and pimcore class names
     * @static
     * @param $type
     * @return string
     */
    public static function getClassNameForType ($type) {
        switch($type) {
            case "user": return "User";
            case "userfolder": return "User_Folder";
            case "role": return "User_Role";
            case "rolefolder": return "User_Role_Folder";
        }
    }
}