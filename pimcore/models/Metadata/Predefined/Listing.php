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
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Metadata\Predefined;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * Contains the results of the list. They are all an instance of Metadata\Predefined
     *
     * @var array
     */
    public $properties = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getDefinitions() {
        return $this->definitions;
    }

    /**
     * @param array $properties
     * @return void
     */
    public function setDefinitions($definitions) {
        $this->definitions = $definitions;
        return $this;
    }

    public static function getByTargetType($type, $subTypes) {
        if ($type != "asset") {
            throw new \Exception("other types than assets are currently not supported");
        }

        $list = new self();

        if ($subTypes && !is_array($subTypes)) {
            $subTypes = array($subTypes);
        }

        if (is_array($subTypes)) {
            $types = array();
            $db = \Pimcore\Resource::get();
            foreach ($subTypes as $item) {
                $types[] = $db->quote($item);
            }

            $condition = "(ISNULL(targetSubtype) OR targetSubtype = '' OR targetSubtype IN (" . implode(',',$types) . "))" ;
            $list->setCondition($condition);
        }
        $list = $list->load();
        return $list;
    }

    /**
     * @param $key
     * @param $language
     * @return \Pimcore\Model\Metadata\Predefined
     */
    public static function getByKeyAndLanguage($key, $language) {

        $db = \Pimcore\Resource::get();
        $list = new self();
        $condition = "name = " . $db->quote($key);
        if ($language) {
            $condition .= " AND language = " . $db->quote($language);
        } else {
            $condition .= " AND (language = '' OR LANGUAGE IS NULL)";
        }
        $list->setCondition($condition);
        $list = $list->load();
        if ($list) {
            return $list[0];
        }
        return null;
    }

}
