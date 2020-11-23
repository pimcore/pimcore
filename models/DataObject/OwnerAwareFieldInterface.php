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

namespace Pimcore\Model\DataObject;

interface OwnerAwareFieldInterface
{
    /**
     * @param mixed $owner
     *
     * @return $this;
     */
    public function __setOwner($owner);

    /**
     * @param string|null $fieldname
     * @return $this
     */
    public function __setOwnerFieldname(?string $fieldname);

    /**
     * @param string|null $language
     * @return $this
     */
    public function __setOwnerLanguage(?string $language);

    /**
     * @return mixed
     */
    public function __getOwner();

    /**
     * @return string|null
     */
    public function __getOwnerFieldname(): ?string;

    /**
     * @return string|null
     */
    public function __getOwnerLanguage(): ?string;
}
