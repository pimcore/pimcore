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

namespace Pimcore\Model\DataObject;

interface OwnerAwareFieldInterface
{
    /**
     * @param mixed $owner
     *
     * @return $this;
     */
    public function _setOwner($owner);

    /**
     * @param string|null $fieldname
     *
     * @return $this
     */
    public function _setOwnerFieldname(?string $fieldname);

    /**
     * @param string|null $language
     *
     * @return $this
     */
    public function _setOwnerLanguage(?string $language);

    /**
     * @return mixed
     */
    public function _getOwner();

    /**
     * @return string|null
     */
    public function _getOwnerFieldname(): ?string;

    /**
     * @return string|null
     */
    public function _getOwnerLanguage(): ?string;
}
