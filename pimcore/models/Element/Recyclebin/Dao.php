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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Recyclebin;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Element\Recyclebin $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function flush()
    {
        $this->db->deleteWhere("recyclebin");
    }
}
