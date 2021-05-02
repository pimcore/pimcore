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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @internal
 */
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
     *
     * @return $this;
     */
    public function _setOwner($owner)
    {
        $this->_owner = $owner;

        return $this;
    }

    /**
     * @return mixed
     */
    public function _getOwner()
    {
        return $this->_owner;
    }

    /**
     * @return string|null
     */
    public function _getOwnerFieldname(): ?string
    {
        return $this->_fieldname;
    }

    /**
     * @return string|null
     */
    public function _getOwnerLanguage(): ?string
    {
        return $this->_language;
    }

    /**
     * @internal
     *
     * @param string|null $fieldname
     *
     * @return $this;
     */
    public function _setOwnerFieldname(?string $fieldname)
    {
        $this->_fieldname = $fieldname;

        return $this;
    }

    /**
     * @internal
     *
     * @param string|null $language
     *
     * @return $this
     */
    public function _setOwnerLanguage(?string $language)
    {
        $this->_language = $language;

        return $this;
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
