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

namespace Pimcore\Model\Redirect;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Redirect\Listing\Dao getDao()
 * @method Model\Redirect[] load()
 * @method Model\Redirect|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Redirect[]
     */
    public function getRedirects()
    {
        return $this->getData();
    }

    /**
     * @param Model\Redirect[]|null $redirects
     *
     * @return $this
     */
    public function setRedirects($redirects)
    {
        return $this->setData($redirects);
    }
}
