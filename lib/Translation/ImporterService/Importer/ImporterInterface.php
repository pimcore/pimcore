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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Translation\ImporterService\Importer;

use Pimcore\Translation\AttributeSet\AttributeSet;

interface ImporterInterface
{
    /**
     * @param AttributeSet $attributeSet
     * @param bool $saveElement
     *
     * @return void
     *
     * @throws \Exception
     */
    public function import(AttributeSet $attributeSet, bool $saveElement = true);
}
