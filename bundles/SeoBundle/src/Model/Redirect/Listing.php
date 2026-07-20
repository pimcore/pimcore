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
    public function isValidOrderKey(string $key): bool
    {
        return in_array($key, [
            'id',
            'type',
            'source',
            'sourceSite',
            'target',
            'targetSite',
            'statusCode',
            'priority',
            'regex',
            'passThroughParameters',
            'active',
            'expiry',
            'creationDate',
            'modificationDate',
            'userOwner',
            'userModification',
        ], true);
    }

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
