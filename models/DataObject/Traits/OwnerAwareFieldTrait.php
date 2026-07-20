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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @internal
 */
trait OwnerAwareFieldTrait
{
    protected mixed $_owner = null;

    protected ?string $_fieldname = null;

    protected ?string $_language = null;

    /**
     *
     * @return $this
     *
     * @internal
     */
    public function _setOwner(mixed $owner): static
    {
        $this->_owner = $owner;

        return $this;
    }

    public function _getOwner(): mixed
    {
        return $this->_owner;
    }

    public function _getOwnerFieldname(): ?string
    {
        return $this->_fieldname;
    }

    public function _getOwnerLanguage(): ?string
    {
        return $this->_language;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function _setOwnerFieldname(?string $fieldname): static
    {
        $this->_fieldname = $fieldname;

        return $this;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function _setOwnerLanguage(?string $language): static
    {
        $this->_language = $language;

        return $this;
    }

    /**
     * @internal
     */
    protected function markMeDirty(bool $dirty = true): void
    {
        if ($this->_owner && $this->_owner instanceof DirtyIndicatorInterface) {
            $this->_owner->markFieldDirty($this->_fieldname, $dirty);
        }
        if ($this->_language && $this->_owner instanceof Localizedfield) {
            $this->_owner->markLanguageAsDirty($this->_language, $dirty);
        }
    }
}
