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

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\UserRole\Dao getDao()
 */
class Folder extends Model\User\AbstractUser
{
    use Model\Element\ChildsCompatibilityTrait;

    /**
     * @var bool
     */
    public $hasChilds;

    /**
     * @param $state
     *
     * @return $this
     */
    public function setHasChilds($state)
    {
        $this->hasChilds = $state;

        return $this;
    }

    /**
     * Returns true if the document has at least one child
     *
     * @return bool
     */
    public function hasChildren()
    {
        if ($this->hasChilds !== null) {
            return $this->hasChilds;
        }

        return $this->getDao()->hasChildren();
    }
}
