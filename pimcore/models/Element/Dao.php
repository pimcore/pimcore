<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

abstract class Dao extends Model\Dao\AbstractDao {

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

