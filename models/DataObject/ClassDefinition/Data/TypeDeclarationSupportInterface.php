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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

interface TypeDeclarationSupportInterface
{
    /**
     * @return string|null
     */
    public function getParameterTypeDeclaration(): ?string;

    /**
     * @return string|null
     */
    public function getReturnTypeDeclaration(): ?string;

    /**
     * @return string|null
     */
    public function getPhpdocInputType(): ?string;

    /**
     * @return string|null
     */
    public function getPhpdocReturnType(): ?string;
}
