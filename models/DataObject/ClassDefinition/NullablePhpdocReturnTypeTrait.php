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

namespace Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal not for public use
 */
trait NullablePhpdocReturnTypeTrait
{
    /**
     * @return string|null
     */
    public function getPhpdocReturnType(): ?string
    {
        if ($this->phpdocType) {
            return $this->phpdocType . '|null';
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getPhpdocInputType(): ?string
    {
        if ($this->phpdocType) {
            return $this->phpdocType . '|null';
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getReturnTypeDeclaration(): ?string
    {
        if ($this->phpdocType) {
            return '?' . $this->phpdocType;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getParameterTypeDeclaration(): ?string
    {
        if ($this->phpdocType) {
            return '?' . $this->phpdocType;
        }

        return null;
    }
}
