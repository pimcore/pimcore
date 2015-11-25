<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

class Folder extends Model\User\AbstractUser {

    /**
     * @var boolean
     */
    public $hasChilds;

    /**
     * @param $state
     * @return $this
     */
    function setHasChilds($state){
        $this->hasChilds= $state;
        return $this;
    }

    /**
     * Returns true if the document has at least one child
     *
     * @return boolean
     */
    public function hasChilds() {
        if ($this->hasChilds !== null) {
            return $this->hasChilds;
        }
        return $this->getDao()->hasChilds();
    }
}
