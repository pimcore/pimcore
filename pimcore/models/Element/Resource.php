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
 * @package    Element
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

abstract class Resource extends Model\Resource\AbstractResource {

    /**
     * @return array
     * @throws \Exception
     */
    public function getParentIds() {
        // collect properties via parent - ids
        $parentIds = array(1);
        $obj = $this->model->getParent();

        if($obj) {
            while($obj) {
                if($obj->getId() == 1) {
                    break;
                }
                if(in_array($obj->getId(), $parentIds)) {
                    throw new \Exception("detected infinite loop while resolving all parents from " . $this->model->getId() . " on " . $obj->getId());
                }

                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }

        return $parentIds;
    }
}

