<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Recyclebin;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Recyclebin $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function flush()
    {
        $this->db->deleteWhere('recyclebin');
    }
}
