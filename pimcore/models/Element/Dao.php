<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Element\AbstractElement $model
 */
abstract class Dao extends Model\Dao\AbstractDao
{

    /**
     * @return array
     * @throws \Exception
     */
    public function getParentIds()
    {
        // collect properties via parent - ids
        $parentIds = [1];
        $obj = $this->model->getParent();

        if ($obj) {
            while ($obj) {
                if ($obj->getId() == 1) {
                    break;
                }
                if (in_array($obj->getId(), $parentIds)) {
                    throw new \Exception("detected infinite loop while resolving all parents from " . $this->model->getId() . " on " . $obj->getId());
                }

                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }

        return $parentIds;
    }
}
