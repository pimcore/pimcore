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

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\DirtyIndicatorInterface;

trait OwnerAwareFieldTrait
{
    /**
     * @var mixed
     */
    protected $_owner;

    /**
     * @var string
     */
    protected $_fieldname;

    /**
     * @var string|null
     */
    protected $_language;

    /**
     * @internal
     *
     * @param mixed $owner
     * @param string $fieldname
     * @param string|null $language
     */
    public function setOwner($owner, string $fieldname, $language = null)
    {
        $this->_owner = $owner;
        $this->_fieldname = $fieldname;
        $this->_language = $language;
    }

    /**
     * @internal
     */
    protected function markMeDirty()
    {
        if ($this->_owner && $this->_owner instanceof DirtyIndicatorInterface) {
            $this->_owner->markFieldDirty($this->_fieldname, true);
        }
        if ($this->_language && $this->_owner instanceof Localizedfield) {
            $this->_owner->markLanguageAsDirty($this->_language);
        }
    }
}
