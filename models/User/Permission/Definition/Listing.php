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
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Permission\Definition;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\Permission\Definition\Listing\Dao getDao()
 * @method Model\User\Permission\Definition[] load()
 * @method Model\User\Permission\Definition current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\User\Permission\Definition[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $definitions = null;

    public function __construct()
    {
        $this->definitions = & $this->data;
    }

    /**
     * @param Model\User\Permission\Definition[] $definitions
     *
     * @return static
     */
    public function setDefinitions($definitions)
    {
        return $this->setData($definitions);
    }

    /**
     * @return Model\User\Permission\Definition[]
     */
    public function getDefinitions()
    {
        return $this->getData();
    }
}
