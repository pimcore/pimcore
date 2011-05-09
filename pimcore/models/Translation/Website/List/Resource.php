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
 * @package    Translation
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Translation_Website_List_Resource extends Translation_Abstract_List_Resource {
    /**
     * Loads a list of translations for the specicifies parameters, returns an array of Translation elements
     *
     * @return array
     */

    public static function getTableName(){
        return Translation_Website_Resource::$_tableName;
    }

    public static function getItemClass () {
        return "Translation_Website";
    }
}
 