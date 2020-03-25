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
 * @package    Redirect
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Redirect;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Redirect\Listing\Dao getDao()
 * @method Model\Redirect[] load()
 * @method Model\Redirect current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $redirects = null;

    public function __construct()
    {
        $this->redirects = & $this->data;
    }

    /**
     * @return Model\Redirect[]
     */
    public function getRedirects()
    {
        return $this->getData();
    }

    /**
     * @param array $redirects
     *
     * @return static
     */
    public function setRedirects($redirects)
    {
        return $this->setData($redirects);
    }
}
