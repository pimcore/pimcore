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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Translation\Admin\Listing;

use Pimcore\Model;

class Resource extends Model\Translation\AbstractTranslation\Listing\Resource {
    /**
     * Loads a list of translations for the specified parameters, returns an array of Translation elements
     *
     * @return array
     */

    public static function getTableName(){
        return Model\Translation\Admin\Resource::$_tableName;
    }

    /**
     * @return string
     */
    public static function getItemClass () {
        return "\\Pimcore\\Model\\Translation\\Admin";
    }
}
 