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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Webservice\Data;

use Pimcore\Model;
use Pimcore\Model\Webservice;

abstract class Document extends Model\Webservice\Data {

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
    public $type;

    /**
     * @var string
     */
    public $key;

    /**
     * @var integer
     */
    public $index;

    /**
     * @var bool
     */
    public $published;

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
     */
    public function map($object) {
        parent::map($object);

        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = array(); 
                foreach ($object->getChilds() as $child) {
                    $item = new Webservice\Data\Document\Listing\Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();

                    $this->childs[] = $item;
                }
            }
        }
    }


}
