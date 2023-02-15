<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\SeoBundle\Model\Redirect;

use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model;

/**
 * @method \Pimcore\Bundle\SeoBundle\Model\Redirect\Listing\Dao getDao()
 * @method Redirect[] load()
 * @method Redirect|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Redirect[]
     */
    public function getRedirects(): array
    {
        return $this->getData();
    }

    /**
     * @param Redirect[]|null $redirects
     *
     * @return $this
     */
    public function setRedirects(?array $redirects): static
    {
        return $this->setData($redirects);
    }
}
