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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Search\Backend\Data;

use Pimcore\Model\Element;

class Id {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @param $webResource
     */
    public function __construct($webResource) {
        $this->id = $webResource->getId();
        if($webResource instanceof Element\ElementInterface) {
            $this->type = Element\Service::getType($webResource);
        } else {
            $this->type = "unknown";
        }
    }

    /**
     * @return int
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(){
        return $this->type;
    }
}