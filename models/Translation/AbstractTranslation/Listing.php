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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Translation\AbstractTranslation;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Translation\AbstractTranslation\Listing\Dao getDao()
 * @method Model\Translation\AbstractTranslation[] load()
 * @method Model\Translation\AbstractTranslation current()
 * @method int getTotalCount()
 * @method void onCreateQuery(callable $callback)
 *
 * @deprecated
 */
class Listing extends \Pimcore\Model\Translation\Listing
{
    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $translations = null;

    public function __construct()
    {
        $this->translations = & $this->data;
    }
}
