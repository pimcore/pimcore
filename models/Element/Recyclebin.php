<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Element\Recyclebin\Dao getDao()
 *
 * @internal
 */
final class Recyclebin extends Model\AbstractModel
{
    public function flush(): void
    {
        $this->getDao()->flush();
        Storage::get('recycle_bin')->deleteDirectory('/');
    }
}
