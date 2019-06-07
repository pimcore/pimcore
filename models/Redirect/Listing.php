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
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $redirects = null;

    /**
     * @return Model\Redirect[]
     */
    public function getRedirects()
    {
        if ($this->redirects === null) {
            $this->getDao()->load();
        }

        return $this->redirects;
    }

    /**
     * @param array $redirects
     *
     * @return $this
     */
    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;

        return $this;
    }
}
