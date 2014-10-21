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

class Asset extends Model\Webservice\Data {

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
    public $filename;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $mimetype;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

    /**
     * @var integer
     */
    public $userOwner;

    /**
     * @var integer
     */
    public $userModification;

    /**
     * @var Webservice\Data\Property[]
     */
    public $properties;

    /**
     * @var object[]
     */
    public $customSettings;

    /**
     * @param $object
     * @param null $options
     */
    public function map($object, $options = null) {
        parent::map($object, $options);

        $settings = $object->getCustomSettings();
        if (!empty($settings)) {
            $this->customSettings = $settings;
        }
        
        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = array();
                foreach ($object->getChilds() as $child) {
                    $item = new Webservice\Data\Asset\Listing\Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();

                    $this->childs[] = $item;
                }
            }
        }

    }
}
