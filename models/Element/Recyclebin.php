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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Element\Recyclebin\Dao getDao()
 *
 * @internal
 */
class Recyclebin extends Model\AbstractModel
{
    public function flush()
    {
        $this->getDao()->flush();
        Storage::get('recycle_bin')->deleteDirectory('/');
    }
}
