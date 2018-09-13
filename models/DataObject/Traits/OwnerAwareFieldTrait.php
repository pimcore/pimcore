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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\DirtyIndicatorInterface;

trait OwnerAwareFieldTrait
{

    /**
     * @var mixed
     */
    protected $owner;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @param $owner
     * @param string $fieldname
     */
    public function setOwner($owner, string $fieldname) {
        $this->owner = $owner;
        $this->fieldname = $fieldname;
    }

    /**
     *
     */
    protected function markMeDirty() {
        if ($this->owner && $this->owner instanceof DirtyIndicatorInterface) {
            $this->owner->markFieldDirty($this->fieldname, true);
        }
    }

}
