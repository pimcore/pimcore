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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_Data_Asset extends Webservice_Data {

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
     * @var Webservice_Data_Property[]
     */
    public $properties;

    /**
     * @var object[]
     */
    public $customSettings;


    public function map($object) {
        parent::map($object);

        $settings = $object->getCustomSettings();
        if (!empty($settings)) {
            $this->customSettings = $settings;
        }
        
        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = array();
                foreach ($object->getChilds() as $child) {
                    $item = new Webservice_Data_Asset_List_Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();

                    $this->childs[] = $item;
                }
            }
        }

    }
}
