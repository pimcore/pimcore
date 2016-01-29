<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice\Data;

use Pimcore\Model;

class Object extends Model\Webservice\Data
{


    /** If set to true then null values will not be exported.
     * @var
     */
    protected static $dropNullValues;

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $parentId;

    /**
     * @var string
     */
    public $key;

    /**
     * @var bool
     */
    public $published;

    /**
     * @var string
     */
    public $type;

    /**
     * @var integer
     */
    public $userOwner;


    /**
     * @var Webservice\Data\Property[]
     */
    public $properties;

    /**
     * @param $object
     * @param null $options
     */
    public function map($object, $options = null)
    {
        parent::map($object);

        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = array();
                foreach ($object->getChilds() as $child) {
                    $item = new Model\Webservice\Data\Object\Listing\Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();
                    $this->childs[] = $item;
                }
            }
        }
    }

    /**
     * @param  $dropNullValues
     */
    public static function setDropNullValues($dropNullValues)
    {
        self::$dropNullValues = $dropNullValues;
    }

    /**
     * @return
     */
    public static function getDropNullValues()
    {
        return self::$dropNullValues;
    }
}
