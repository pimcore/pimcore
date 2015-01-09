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

class ClassDefinition extends Model\Webservice\Data {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userOwner;

    /**
     * @var int
     */
    public $userModification;

    /**
     * Name of the parent class if set
     *
     * @var string
     */
    public $parentClass;

    /**
     * @var boolean
     */
    public $allowInherit = false;

    /**
     * @var boolean
     */
    public $allowVariants = false;

    /**
     * @var boolean
     */
    public $showVariants = false;

    /**
     * @var array
     */
    public $fieldDefinitions;

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $previewUrl;

    /**
     * @param $class
     */
    public function map ($class) {

        $arr = $class->fieldDefinitions;
        $result = array();
        foreach ($arr as $item) {
            $result[] = $item;
        }
        $class->fieldDefinitions = $item;

        parent::map($class);
    }
}
